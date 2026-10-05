<?php

namespace OpenFiber\Soap;

use SoapClient;

/**
 * Base SoapClient extension that consolidates common SOAP client functionality
 * and provides a foundation for both simple and complex SOAP client implementations.
 *
 * This class addresses the repetitive pattern of anonymous SoapClient extensions
 * found in SoapCommunicator.php and DynamicSoapClient.php, following AGENTS.md Rule 11
 * by providing a single, atomic, extensible base class.
 *
 * @package OpenFiber\Soap
 */
class BaseSoapClientExtension extends SoapClient
{
    /**
     * Array of WSDL options for the SOAP client
     *
     * @var array
     */
    protected array $wsdlOptions;

    /**
     * BaseSoapClientExtension constructor
     *
     * @param string $wsdl The WSDL URL or data URI
     * @param array $options Array of SOAP options
     */
    public function __construct(string $wsdl, array $options = [])
    {
        $this->wsdlOptions = $options;
        parent::__construct($wsdl, $options);
    }

    /**
     * Handle SOAP request with common functionality
     *
     * This method provides a single extension point for handling SOAP requests,
     * allowing subclasses to customize request handling while maintaining
     * consistent error handling and logging patterns.
     *
     * @param string $request The SOAP request XML
     * @param string $location The endpoint URL
     * @param string $action The SOAP action
     * @param int $version The SOAP version
     * @param bool $oneWay Whether this is a one-way message
     * @return ?string The SOAP response XML
     */
    #[Override]
    public function __doRequest(string $request, string $location, string $action, int $version, bool $oneWay = false): ?string
    {
        return $this->handleSoapRequest($request, $location, $action, $version, $oneWay);
    }

    /**
     * Protected method to handle SOAP requests
     *
     * Subclasses can override this method to implement custom request handling
     * while still benefiting from the base class constructor and common patterns.
     *
     * @param string $request The SOAP request XML
     * @param string $location The endpoint URL
     * @param string $action The SOAP action
     * @param int $version The SOAP version
     * @param bool $oneWay Whether this is a one-way message
     * @return ?string The SOAP response XML
     */
    protected function handleSoapRequest(string $request, string $location, string $action, int $version, bool $oneWay = false): ?string
    {
        return parent::__doRequest($request, $location, $action, $version, $oneWay);
    }
}