<?php
/**
 * Created by PhpStorm.
 * User: chendong
 * Date: 15/5/22
 * Time: 上午11:35
 */

namespace cdcchen\filesystem;


/**
 * Class PathBuilder
 * @package cdcchen\filesystem
 */
class PathBuilder
{
    /**
     * @var string
     */
    protected $basePath;
    /**
     * @var string
     */
    protected $pathName;
    /**
     * @var string
     */
    protected $fileName;

    /**
     * @param null|string $key
     * @return array|mixed
     */
    protected static function placeHolders($key = null)
    {
        $places = [
            '{year}' => date('Y'),
            '{month}' => date('m'),
            '{day}' => date('d'),
            '{hour}' => date('H'),
            '{minute}' => date('i'),
            '{second}' => date('s'),
            '{week}' => date('W'),
            '{wday}' => date('w'),
            '{timestamp}' => time(),
            '{uniqid}' => uniqid(),
        ];

        return empty($key) ? $places : $places[$key];
    }

    /**
     * PathBuilder constructor.
     * @param string $basePath
     */
    public function __construct($basePath = '')
    {
        $this->basePath = $basePath;
    }

    /**
     * @param string $pathFormat
     * @param null|string $prefix
     * @param null|string $suffix
     * @return $this
     */
    public function buildPathName($pathFormat, $prefix = null, $suffix = null)
    {
        $pathName = strtr($pathFormat, self::placeHolders());

        if ($prefix) {
            $pathName = $prefix . DIRECTORY_SEPARATOR . $pathName;
        }

        if ($suffix) {
            $pathName .= DIRECTORY_SEPARATOR . $suffix;
        }

        $this->pathName = static::normalizePath($pathName);
        return $this;
    }

    /**
     * @param int $mode
     * @param bool $recursive
     * @param null $context
     * @return mixed
     */
    public function createPath($mode = 0755, $recursive = true, $context = null)
    {
        $basePath = (stripos($this->pathName, '/') === 0) ? '' : $this->basePath;
        $path = $basePath . DIRECTORY_SEPARATOR . $this->pathName;
        $path = static::normalizePath($path);
        return $context ? mkdir($path, $mode, $recursive, $context) : mkdir($path, $mode, $recursive);
    }

    /**
     * @param string $fileFormat
     * @param string $extensionName
     * @param bool $includeDot
     * @return $this
     */
    public function buildFileName($fileFormat, $extensionName = '', $includeDot = false)
    {
        $fileName = strtr($fileFormat, self::placeHolders());
        if ($extensionName) {
            $fileName .= ($includeDot ? '' : '.') . $extensionName;
        }

        $this->fileName = $fileName;
        return $this;
    }

    /**
     * @param string $ds
     * @return string
     */
    public function getFilePath($ds = DIRECTORY_SEPARATOR)
    {
        $basePath = (stripos($this->pathName, '/') === 0) ? '' : $this->basePath;
        $path = $basePath . $ds . $this->pathName . $ds . $this->fileName;
        return static::normalizePath($path, $ds);
    }

    /**
     * @param null|string $baseUrl
     * @return string
     */
    public function getFileUrl($baseUrl = null)
    {
        $baseUrl = $baseUrl ? rtrim($baseUrl, '/') : '';
        $basePath = (stripos($this->pathName, '/') === 0) ? '' : $this->basePath;
        $path = '/' . $basePath . '/' . $this->pathName . '/';

        return $baseUrl . static::normalizePath($path, '/') . '/' . $this->fileName;
    }

    /**
     * @return string
     */
    public function getPathname()
    {
        return dirname($this->getFilePath());
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return basename($this->getFilePath());
    }


    /**
     * Normalizes a file/directory path.
     * The normalization does the following work:
     *
     * - Convert all directory separators into `DIRECTORY_SEPARATOR` (e.g. "\a/b\c" becomes "/a/b/c")
     * - Remove trailing directory separators (e.g. "/a/b/c/" becomes "/a/b/c")
     * - Turn multiple consecutive slashes into a single one (e.g. "/a///b/c" becomes "/a/b/c")
     * - Remove ".." and "." based on their meanings (e.g. "/a/./b/../c" becomes "/a/c")
     *
     * @param string $path the file/directory path to be normalized
     * @param string $ds the directory separator to be used in the normalized result. Defaults to `DIRECTORY_SEPARATOR`.
     * @return string the normalized file/directory path
     */
    public static function normalizePath($path, $ds = DIRECTORY_SEPARATOR)
    {
        $path = rtrim(strtr($path, '/\\', $ds . $ds), $ds);
        if (strpos($ds . $path, "{$ds}.") === false && strpos($path, "{$ds}{$ds}") === false) {
            return $path;
        }
        // the path may contain ".", ".." or double slashes, need to clean them up
        $parts = [];
        foreach (explode($ds, $path) as $part) {
            if ($part === '..' && !empty($parts) && end($parts) !== '..') {
                array_pop($parts);
            } elseif ($part === '.' || $part === '' && !empty($parts)) {
                continue;
            } else {
                $parts[] = $part;
            }
        }
        $path = implode($ds, $parts);
        return $path === '' ? '.' : $path;
    }
}