<?php namespace WebEd\Base\Elfinder\Http\Controllers;

use WebEd\Base\Http\Controllers\BaseAdminController;
use WebEd\Base\Elfinder\Support\Connector;

class ElfinderController extends BaseAdminController
{
    protected $module = 'webed-elfinder';

    public function __construct()
    {
        parent::__construct();
    }

    public function getIndex()
    {
        $this->breadcrumbs->addLink(trans($this->module . '::base.all_files'));

        $this->getDashboardMenu('webed-elfinder');

        $this->setPageTitle(trans($this->module . '::base.all_files'));

        return do_filter(BASE_FILTER_CONTROLLER, $this, WEBED_ELFINDER, 'index.get')->viewAdmin('index');
    }

    public function getStandAlone()
    {
        return do_filter(BASE_FILTER_CONTROLLER, $this, WEBED_ELFINDER, 'stand-alone.get')->viewAdmin('stand-alone');
    }

    public function getElfinderView()
    {
        return do_filter(BASE_FILTER_CONTROLLER, $this, WEBED_ELFINDER, 'elfinder-view.get')->viewAdmin('elfinder-view');
    }

    public function anyConnector()
    {
        $roots = config($this->module . '.roots', []);

        if (empty($roots)) {
            $dirs = (array)config($this->module . '.dir', ['uploads']);

            if (!is_dir($dirs[0])) {
                mkdir($dirs[0], 0777, true);
            }

            foreach ($dirs as $dir) {
                $path = $dir;
                $url = $dir;
                $roots[] = [
                    'driver' => 'LocalFileSystem', // driver for accessing file system (REQUIRED)
                    'path' => public_path($path), // path to files (REQUIRED)
                    'tmpPath' => public_path($path),
                    'URL' => url($url), // URL to files (REQUIRED)
                    'accessControl' => 'WebEd\Base\Elfinder\Http\Controllers\ElfinderController::checkAccess', // filter callback (OPTIONAL),
                    'autoload' => true,
                    'uploadDeny' => ['text/x-php', 'application/x-shockwave-flash'],
                    'uploadAllow' => [],
                    'uploadOrder' => ['deny', 'allow'],
                    'uploadOverwrite' => false,
                    'attributes' => [
                        [
                            'pattern' => '/\.(txt|html|php|py|pl|sh|xml|php|sh)$/i',
                            'read' => true,
                            'write' => false,
                            'locked' => false,
                            'hidden' => false
                        ]
                    ]
                ];
            }

            $disks = (array)config($this->module . '.disks', []);
            foreach ($disks as $key => $root) {
                if (is_string($root)) {
                    $key = $root;
                    $root = [];
                }
                $disk = app('filesystem')->disk($key);
                if ($disk instanceof \Illuminate\Filesystem\FilesystemAdapter) {
                    $defaults = [
                        'driver' => 'Flysystem',
                        'filesystem' => $disk->getDriver(),
                        'alias' => $key,
                    ];
                    $roots[] = array_merge($defaults, $root);
                }
            }
        }

        $opts = ['roots' => $roots, 'debug' => true];

        $connector = new Connector(new \elFinder($opts));
        $connector->run();
        return $connector->getResponse();
    }

    public static function checkAccess($attr, $path, $data, $volume)
    {
        return strpos(basename($path), '.') === 0       // if file/folder begins with '.' (dot)
            ? !($attr == 'read' || $attr == 'write')    // set read+write to false, other (locked+hidden) set to true
            : null;                                    // else elFinder decide it itself
    }
}
