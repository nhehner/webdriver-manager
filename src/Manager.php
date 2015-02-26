<?php
namespace Peridot\WebDriverManager;

use Peridot\WebDriverManager\Binary\BinaryDecompressorInterface;
use Peridot\WebDriverManager\Binary\BinaryRequestInterface;
use Peridot\WebDriverManager\Binary\ChromeDriver;
use Peridot\WebDriverManager\Binary\SeleniumStandalone;
use Peridot\WebDriverManager\Binary\StandardBinaryRequest;
use Peridot\WebDriverManager\Binary\ZipDecompressor;

class Manager
{
    /**
     * @var array
     */
    protected $binaries;

    /**
     * @var BinaryRequestInterface
     */
    protected $request;

    /**
     * @var BinaryDecompressorInterface
     */
    protected $decompressor;

    /**
     * @param BinaryRequestInterface $request
     */
    public function __construct(
        BinaryRequestInterface $request = null,
        BinaryDecompressorInterface $decompressorInterface = null
    ) {
        $this->request = $request;
        $this->decompressor = $decompressorInterface;
        $this->binaries = [
            new SeleniumStandalone($this->getBinaryRequest()),
            new ChromeDriver($this->getBinaryRequest(), $this->getBinaryDecompressor())
        ];
    }

    /**
     * Get the BinaryRequestInterface responsible for fetching remote binaries.
     *
     * @return BinaryRequestInterface|StandardBinaryRequest
     */
    public function getBinaryRequest()
    {
        if ($this->request === null) {
            return new StandardBinaryRequest();
        }
        return $this->request;
    }

    /**
     * Get the BinaryDecompressorInterface responsible for decompressing
     * compressed binaries.
     *
     * @return BinaryDecompressorInterface|ZipDecompressor
     */
    public function getBinaryDecompressor()
    {
        if ($this->decompressor === null) {
            return new ZipDecompressor();
        }
        return $this->decompressor;
    }

    /**
     * Fetch and save binaries.
     *
     * @return void
     */
    public function update()
    {
        foreach ($this->binaries as $binary) {
            $binary->fetchAndSave($this->getInstallPath());
        }
    }

    /**
     * Get the installation path of binaries.
     *
     * @return string
     */
    public function getInstallPath()
    {
        return realpath(__DIR__ . '/../binaries');
    }
} 
