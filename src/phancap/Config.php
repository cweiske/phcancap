<?php
namespace phancap;

class Config
{
    /**
     * Full file system path to cache directory
     * @var string
     */
    public $cacheDir;

    /**
     * Full URL to cache directory
     * @var string
     */
    public $cacheDirUrl;


    /**
     * List of config file paths that were tried to load
     * @var array
     */
    public $cfgFiles = array();

    /**
     * If a configuration file could be found
     * @var boolean
     */
    public $cfgFileExists;

    /**
     * Credentials for access
     *
     * Array of
     *     username => secret key
     * entries (used for signature).
     *
     * Boolean true to allow access in every case,
     * false to completely disable it.
     *
     * @var array|boolean
     */
    public $access = true;

    /**
     * Redirect the browser to the cache URL.
     * If disabled, the file is directly delivered.
     *
     * Helpful for debugging since it does not change the browser's URL.
     *
     * @var boolean
     */
    public $redirect = true;

    /**
     * How long requests with an old timestamp may be used.
     * 2 days default.
     *
     * @var integer
     */
    public $timestampMaxAge = 'P2D';

    /**
     * Cache time of downloaded screenshots.
     * When the file is as older than this, it gets re-created.
     * The user can override that using the "smaxage" parameter.
     *
     * Defaults to 1 week.
     *
     * @var integer Lifetime in seconds
     */
    public $screenshotMaxAge = 'P1W';

    /**
     * Minimum age of a screeshot.
     * A user cannot set the max age parameter below it.
     *
     * Defaults to 1 hour.
     *
     * @var integer Minimum lifetime in seconds
     */
    public $screenshotMinAge = 'PT1H';


    public function __construct()
    {
        $this->cacheDir    = getcwd() . '/imgcache/';
        $this->cacheDirUrl = $this->getCurrentUrlDir() . '/imgcache/';

        $this->timestampMaxAge  = Options::validateAge($this->timestampMaxAge);
        $this->screenshotMaxAge = Options::validateAge($this->screenshotMaxAge);
        $this->screenshotMinAge = Options::validateAge($this->screenshotMinAge);

        $this->loadConfigFilePaths();
    }

    public function load()
    {
        $this->cfgFileExists = false;
        foreach ($this->cfgFiles as $file) {
            if (file_exists($file)) {
                $this->cfgFileExists = true;
                $this->loadFile($file);
                break;
            }
        }

        $this->setupCheck();
    }

    /**
     * Load possible configuration file paths into $this->cfgFiles.
     *
     * @return void
     */
    protected function loadConfigFilePaths()
    {
        $pharFile = \Phar::running();
        if ($pharFile == '') {
            $this->cfgFiles[] = __DIR__ . '/../../data/phancap.config.php';
        } else {
            //remove phar:// from the path
            $this->cfgFiles[] = substr($pharFile, 7) . '.config.php';
        }

        //TODO: add ~/.config/phancap.php

        $this->cfgFiles[] = '/etc/phancap.php';
    }

    protected function loadFile($filename)
    {
        include $filename;
        $vars = get_defined_vars();
        foreach ($vars as $k => $value) {
            $this->$k = $value;
        }
    }

    public function setupCheck()
    {
        if (!is_dir($this->cacheDir)) {
            throw new \Exception('Cache directory does not exist: ' . $this->cacheDir);
        }
        if (!is_writable($this->cacheDir)) {
            throw new \Exception('Cache directory is not writable: ' . $this->cacheDir);
        }
    }

    protected function getCurrentUrl()
    {
        if (!isset($_SERVER['REQUEST_SCHEME'])) {
            $_SERVER['REQUEST_SCHEME'] = 'http';
        }
        return $_SERVER['REQUEST_SCHEME'] . '://'
            . $_SERVER['HTTP_HOST']
            . preg_replace('/#.*$/', '', $_SERVER['REQUEST_URI']);
    }

    /**
     * @return string Directory of URL without trailing slash,
     *                and without .phar file
     */
    protected function getCurrentUrlDir()
    {
        $url = $this->getCurrentUrl();
        $url = preg_replace('/\?.*$/', '', $url);
        if (substr($url, -1) != '/') {
            $url = substr($url, 0, -strlen(basename($url)) - 1);
        }
        if (\Phar::running()) {
            //remove .phar file name
            $url = substr($url, 0, -strlen(basename($url)) - 1);
        }

        return $url;
    }
}
?>
