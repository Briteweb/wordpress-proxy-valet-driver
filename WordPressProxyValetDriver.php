<?php

namespace Valet\Drivers\Custom;

use Valet\Drivers\Specific\WordPressValetDriver;

/**
 * Class WordPressProxyValetDriver
 * @author  Jeff Sagal
 * @package Briteweb
 * @url     https://github.com/Briteweb/wordpress-proxy-valet-driver
 *
 * This Valet Driver will proxy given file types to
 * a production URL. This is useful for local dev -
 * you don't need to worry about syncing the uploads
 * folder to your local copy, it'll hit the production
 * server for those files.
 *
 * Configuration is simple. Add `.uploads-proxy` to the
 * WordPress root. The file should contain only one line:
 * the URL of the site you need to proxy to.
 *
 * ex) https://briteweb.com
 *
 */
class WordPressProxyValetDriver extends WordPressValetDriver
{
    /**
     * The file types that should be served
     * through a proxy.
     *
     * @var array
     */
    protected $proxyable = [
        'png',
        'jpg',
        'jpeg',
        'gif',
        'ico',
        'svg',
        'webm',
        'mp4',
    ];

    /**
     * The file to check for when determining
     * whether to use this driver.
     *
     * Should contain a single line: the URL
     * that proxy requests will be sent to.
     *
     * @var string
     */
    protected $configFile = '.uploads-proxy';

    /**
     * Determine if the driver serves the request.
     *
     * @param  string $sitePath
     * @param  string $siteName
     * @param  string $uri
     * @return bool
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return file_exists("$sitePath/$this->configFile");
    }

    /**
     * Determine if the incoming request is for a static file.
     * Return early if it's going to be served through a proxy.
     *
     * @param string $sitePath
     * @param string $siteName
     * @param string $uri
     * @return string
     */
    public function isStaticFile($sitePath, $siteName, $uri)
    {
        if ($this->shouldProxy($uri) && ! $this->isActualFile($staticFilePath = $sitePath.$uri)) {
            return true;
        }

        return parent::isStaticFile($sitePath, $siteName, $uri);
    }

    /**
     * Serve the static file at the given path.
     *
     * @param string $staticFilePath
     * @param string $sitePath
     * @param string $siteName
     * @param string $uri
     */
    public function serveStaticFile(string $staticFilePath, string $sitePath, string $siteName, string $uri): void
    {
        if ($this->shouldProxy($uri) && !file_exists($staticFilePath)) {
            $proxy = $this->getProxyUrl($sitePath);

            header("Location: $proxy/$uri");

            return;
        }

        parent::serveStaticFile($staticFilePath, $sitePath, $siteName, $uri);
    }

    /**
     * Determine if the URI should be proxied.
     *
     * @param $uri
     * @return bool
     */
    public function shouldProxy($uri)
    {
        $extension = pathinfo($uri, PATHINFO_EXTENSION);
        $dirName = pathinfo($uri, PATHINFO_DIRNAME);

        return in_array($extension, $this->proxyable) &&
            strpos($dirName, 'content/uploads') !== false;
    }

    /**
     * Get the URL from the config file.
     *
     * @param $sitePath
     * @return string
     */
    protected function getProxyUrl($sitePath)
    {
        $proxy = rtrim(file_get_contents("$sitePath/$this->configFile"), '/');

        return rtrim($proxy);
    }

}
