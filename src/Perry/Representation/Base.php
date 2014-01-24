<?php
namespace Perry\Representation;

class Base
{
    protected $genericMembers = array();

    /**
     * @param null|array|object|string $inputData
     * @throws \Exception
     */
    public function __construct($inputData)
    {
        $inputData = $this->cleanInputData($inputData);

        foreach ($inputData as $key => $value) {
            $method = 'set'.ucfirst($key);
            // if there is a setter method for this call the setter
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            } else {
                $this->genericMembers[$key] = $value;
            }
        }
    }

    /**
     * clean input data
     *
     * @param array|object|null|string $inputData
     * @throws \Exception
     * @returns array
     */
    private function cleanInputData($inputData)
    {
        switch (true) {
            case is_null($inputData):
                throw new \Exception("got NULL in Base Construtor");
            case is_string($inputData):
                $inputData = json_decode($inputData);
                break;
            case is_object($inputData):
                $inputData = get_object_vars($inputData);
                break;
            default:
        }

        if (!is_array($inputData) && !is_object($inputData)) {
            throw new \Exception("inputData is not an array, and therefor can't be traversed");
        }

        return $inputData;
    }

    /**
     * @param string $key
     * @return \Perry\Representation\Base|string|integer|float|null
     */
    public function __get($key)
    {
        if (isset($this->genericMembers[$key])) {
            return $this->genericMembers[$key];
        }

        return null;
    }

    /**
     * @param string $method
     * @param array $args
     * @return \Perry\Representation\Base
     * @throws \Exception
     */
    public function __call($method, $args)
    {
        if (isset($this->{$method}) && $this->{$method} instanceof Interfaces\CanRefer) {
            /**
             * @var Interfaces\CanRefer $reference
             */
            $reference = $this->{$method};

            return $reference->call($args);
        } else {
            throw new \Exception("$method does not exist with this object");
        }
    }

    /**
     * @param string $url
     * @param string $representation
     * @throws \Exception
     * @return string
     */
    protected static function doGetRequest($url, $representation)
    {
        $opts = array(
            'http' => array(
                'method' => "GET",
                'header' => "Accept-language: en\r\n".
                    "Accept: application/$representation+json\r\n",
            ),
            'socket' => array(
                'bindto' => \Perry\Setup::$bindToIp
            )
        );

        $context = stream_context_create($opts);

        if (false === ($data = @file_get_contents($url, false, $context))) {

            if (false === $headers = (@get_headers($url, 1))) {
                throw new \Exception("could not connect to api");
            }

            throw new \Exception("an error occured with the http request: ".$headers[0]);
        }

        return $data;
    }
}
