<?php namespace WebEd\Base\Elfinder\Support;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class Connector
 * Extended elFinder connector
 * @package WebEd\Base\Elfinder\Support
 * @author Dmitry (dio) Levashov
 * @thanks to Barrydvh. See https://github.com/barryvdh/laravel-elfinder
 *
 * @edited by Tedozi Manson <duyphan.developer@gmail.com>
 */
class Connector extends \elFinderConnector
{
    /** @var Response */
    protected $response;

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Output json
     * @param  array $data to output
     * @return void
     * @author Dmitry (dio) Levashov
     **/
    protected function output(array $data)
    {

        $header = isset($data['header']) ? $data['header'] : $this->header;
        unset($data['header']);

        $headers = array();
        if ($header) {
            foreach ((array)$header as $headerString) {
                if (strpos($headerString, ':') !== false) {
                    list($key, $value) = explode(':', $headerString, 2);
                    $headers[$key] = $value;
                }
            }
        }

        if (isset($data['pointer'])) {
            $this->response = new StreamedResponse(function () use ($data) {
                rewind($data['pointer']);
                fpassthru($data['pointer']);
                if (!empty($data['volume'])) {
                    $data['volume']->close($data['pointer'], $data['info']['hash']);
                }
            }, \Constants::SUCCESS_CODE, $headers);
        } else {
            if (!empty($data['raw']) && !empty($data['error'])) {
                $this->response = new JsonResponse($data['error'], \Constants::ERROR_CODE);
            } else {
                $this->response = new JsonResponse($data, \Constants::SUCCESS_CODE, $headers);
            }
        }
    }
}
