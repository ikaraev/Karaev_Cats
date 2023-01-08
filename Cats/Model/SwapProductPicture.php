<?php

declare(strict_types=1);

namespace Karaev\Cats\Model;

use Magento\Catalog\Model\Product;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Filesystem;
use Karaev\Cats\Helper\ConfigHelper;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\MediaStorage\Model\File\Uploader;
use Magento\Framework\App\Area;

/**
 * Class SwapProductPicture
 * @package Karaev\Cats\Model
 */
class SwapProductPicture
{
    const PNG_FORMAT = '.png';

    const MEDIA_ATTRIBUTES = ['image', 'small_image', 'thumbnail'];

    private File $file;

    private State $state;

    private Gallery $gallery;

    private Filesystem $filesystem;

    private Config $mediaConfig;

    private ConfigHelper $configHelper;

    private CatServiceApi $catServiceApi;

    private ProductRepository $productRepository;

    private SearchCriteriaBuilder $searchCriteriaBuilder;

    private WriteInterface $mediaDirectory;

    /**
     * @param File $file
     * @param State $state
     * @param Gallery $gallery
     * @param Config $mediaConfig
     * @param Filesystem $filesystem
     * @param FileFactory $fileFactory
     * @param ConfigHelper $configHelper
     * @param CatServiceApi $catServiceApi
     * @param ProductRepository $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @throws FileSystemException
     */
    public function __construct(
        File $file,
        State $state,
        Gallery $gallery,
        Config $mediaConfig,
        Filesystem $filesystem,
        ConfigHelper $configHelper,
        CatServiceApi $catServiceApi,
        ProductRepository $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->file = $file;
        $this->state = $state;
        $this->gallery = $gallery;
        $this->mediaConfig = $mediaConfig;
        $this->filesystem = $filesystem;
        $this->configHelper = $configHelper;
        $this->catServiceApi = $catServiceApi;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws StateException
     */
    public function run(): void
    {
        if (!$this->configHelper->isModuleEnable()) {
            return;
        }

        $this->state->setAreaCode(Area::AREA_ADMINHTML);

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $productList = $this->productRepository->getList($searchCriteria);
        $catPhrase = $this->configHelper->getCatPhrase();
        $isRemoveProductImages = $this->configHelper->isRemoveProductImages();

        $mediaPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();

        foreach ($productList->getItems() as $product) {

            if ($isRemoveProductImages) {
                $this->removeProductImages($product);
            }

            $this->addCatImageFromResourceToProduct($product, $catPhrase, $mediaPath);
        }
    }

    /**
     * @param Product $product
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws StateException
     */
    public function removeProductImages(Product $product): void
    {
        $existingMediaGalleryEntries = $product->getMediaGalleryImages();
        foreach ($existingMediaGalleryEntries as $entry) {
            $this->gallery->deleteGallery($entry->getValueId());

            $this->removeDeletedImages($entry->getFile());
        }
        $product->setMediaGalleryEntries([]);

        $this->productRepository->save($product);
    }

    /**
     * @param string $filename
     * @param string $dest
     * @return bool
     * @throws LocalizedException
     */
    public function readFile(string $filename, string $dest): bool
    {
        $directory = dirname($dest);

        $this->file->checkAndCreateFolder($directory);
        return $this->file->read($filename, $dest);
    }

    /**
     * Download form the cat resource image and save in file system.
     *
     * @param Product $product
     * @param string|null $catPhrase
     * @param $mediaPath
     * @return string|bool
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws StateException
     */
    private function addCatImageFromResourceToProduct(Product $product, ?string $catPhrase = null, $mediaPath): void
    {
        $pictureApi = $catPhrase ? $this->catServiceApi->getApiUrlWithPhraseParameter($catPhrase) :
            $this->catServiceApi->getRandomCatApiUrl();

        $fileName = $this->getUniquePictureName();

        $dispersionPath = Uploader::getDispersionPath($fileName);
        $fileName = $dispersionPath . DIRECTORY_SEPARATOR . $fileName;

        $fileName = $this->getNotDuplicatedFilename($fileName, $dispersionPath);

        $destinationFile = $this->mediaConfig->getTmpMediaPath($fileName);

        if ($this->readFile($pictureApi, $mediaPath . $destinationFile)) {
            $product->addImageToMediaGallery($mediaPath . $destinationFile, self::MEDIA_ATTRIBUTES, true, false);

            $this->productRepository->save($product);
        }
    }

    /**
     * Get unique picture name via uniqid()
     * Workaround solution.
     *
     * @return string
     */
    public function getUniquePictureName(): string
    {
        return uniqid() . self::PNG_FORMAT;
    }

    /**
     * @param $fileName
     * @param $dispersionPath
     * @return string
     */
    protected function getNotDuplicatedFilename($fileName, $dispersionPath): string
    {
        $fileMediaName = $dispersionPath . DIRECTORY_SEPARATOR
            . Uploader::getNewFileName($this->mediaConfig->getMediaPath($fileName));
        $fileTmpMediaName = $dispersionPath . DIRECTORY_SEPARATOR
            . Uploader::getNewFileName($this->mediaConfig->getTmpMediaPath($fileName));

        if ($fileMediaName != $fileTmpMediaName) {
            if ($fileMediaName != $fileName) {
                return $this->getNotDuplicatedFilename(
                    $fileMediaName,
                    $dispersionPath
                );
            } elseif ($fileTmpMediaName != $fileName) {
                return $this->getNotDuplicatedFilename(
                    $fileTmpMediaName,
                    $dispersionPath
                );
            }
        }

        return $fileMediaName;
    }

    /**
     * @param string $filePath
     * @return bool
     * @throws FileSystemException
     */
    protected function removeDeletedImages(string $filePath): bool
    {
        $catalogPath = $this->mediaConfig->getBaseMediaPath();

        return $this->mediaDirectory->delete($catalogPath . DIRECTORY_SEPARATOR . $filePath);
    }
}
