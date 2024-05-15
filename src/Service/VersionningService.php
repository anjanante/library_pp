<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class VersionningService{
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var String
     */
    private $defaultVersion;

    /**
     * To get current request and service default parameter
     *
     * @param RequestStack $rs
     * @param ParameterBagInterface $pbi
     */
    public function __construct(RequestStack $rs, ParameterBagInterface $pbi)
    {
        $this->requestStack = $rs;
        $this->defaultVersion = $pbi->get('default_api_version');
    }

    /**
     * Get version from header "accept"
     * @return string
     */
    public function getVersion(): string
    {
        $sVersion = $this->defaultVersion;

        $oRequest = $this->requestStack->getCurrentRequest();
        $sAccept  = $oRequest->headers->get('Accept');
        $aHeader  = explode(';', $sAccept);

        foreach ($aHeader as $sHeader) {
            if(strpos($sHeader, 'version') !== false){
                $aVersion = explode('=', $sHeader);
                $sVersion = trim($aVersion[1]);
                break;
            }
        }

        return $sVersion;
    }
}