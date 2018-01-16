<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Container;
use AppBundle\Entity\ContainerStatus;
use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\WrongInputException;
use AppBundle\Service\LxdApi\MonitoringApi;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as OAS;

class MonitoringController extends Controller
{
    /**
     * List all available Logfiles for a Container
     *
     * @Route("/monitoring/logs/containers/{containerId}", name="list_all_logfiles_from_container", methods={"GET"})
     * @throws ElementNotFoundException
     * @throws \Httpful\Exception\ConnectionErrorException
     * @throws WrongInputException
     *
     * @OAS\Get(path="/monitoring/logs/containers/{containerId}",
     *     tags={"monitoring"},
     *     @OAS\Parameter(
     *      description="ID of the Container",
     *      in="path",
     *      name="containerId",
     *      required=true,
     *          @OAS\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=200,
     *          description="List of all available logfiles for the Container as an array under the attribute logs",
     *          @OAS\Schema(
     *              type="array"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No Container for the id found",
     *      ),
     *     @OAS\Response(
     *          response=400,
     *          description="Returns an LXD Error 'LXD-Error - {LXD-Response}' ",
     *      ),
     * )
     */
    public function listAllLogfilesForContainer($containerId, MonitoringApi $api){
        $container = $this->getDoctrine()->getRepository(Container::class)->find($containerId);

        if (!$container) {
            throw new ElementNotFoundException(
                'No Container for ID '.$containerId.' found'
            );
        }

        $result = $api->getListOfLogfilesFromContainer($container);

        if($result->code != 200){
            throw new WrongInputException("LXD-Error - ".$result->body->error);
        }
        if($result->body->status_code != 200){
            throw new WrongInputException("LXD-Error - ".$result->body->error);
        }

        //Parse logfile names
        $logfileArray = array();
        for($i=0; $i<sizeof($result->body->metadata); $i++){
            $logfileArray[] = $this->parseLogfileUrlToLogfileName($result->body->metadata[$i]);
        }

        $response = ['logs' => $logfileArray];
        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($response, 'json');
        return new Response($response);
    }

    /**
     * Get the content of a single Logfile
     *
     * @Route("/monitoring/logs/containers/{containerId}/{logfile}", name="get_single_log_from_container", methods={"GET"})
     * @param $containerId
     * @param $logfile
     * @param MonitoringApi $api
     * @throws ElementNotFoundException
     * @throws WrongInputException
     * @return Response
     *
     * @OAS\Get(path="/monitoring/logs/containers/{containerId}/{logfile}",
     *     tags={"monitoring"},
     *     @OAS\Parameter(
     *      description="ID of the Container",
     *      in="path",
     *      name="containerId",
     *      required=true,
     *          @OAS\Schema(
     *              type="integer"
     *          ),
     *      ),
     *     @OAS\Parameter(
     *      description="Filename of the Logfile, including type",
     *      in="path",
     *      name="logfile",
     *      required=true,
     *          @OAS\Schema(
     *              type="string"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=200,
     *          description="Returns the File content as text/plain",
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No Container for the id found",
     *      ),
     *     @OAS\Response(
     *          response=400,
     *          description="Returns an LXD Error 'LXD-Error - {LXD-Response}' ",
     *      ),
     * )
     */
    public function getSingleLogfileFromContainer($containerId, $logfile, MonitoringApi $api){
        $container = $this->getDoctrine()->getRepository(Container::class)->find($containerId);

        if (!$container) {
            throw new ElementNotFoundException(
                'No Container for ID '.$containerId.' found'
            );
        }
        $result = $api->getSingleLogfileFromContainer($container, $logfile);

        if($result->code != 200){
            $result = json_decode($result->body);
            throw new WrongInputException("LXD-Error - ".$result->error);
        }

        $response = new Response();
        $response->setContent($result->body);
        $response->headers->set('Content-Type', 'text/plain');
        return $response;
    }

    /**
     * Get the StatusCheck results for a Container
     * @Route("/monitoring/checks/containers/{containerId}", name="get_status_check_container", methods={"GET"})
     * @throws ElementNotFoundException
     *
     * @OAS\Get(path="/monitoring/checks/containers/{containerId}",
     *     tags={"monitoring"},
     *     @OAS\Parameter(
     *      description="ID of the Container",
     *      in="path",
     *      name="containerId",
     *      required=true,
     *          @OAS\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=200,
     *          description="Returns the ContainerStatus",
     *          @OAS\JsonContent(ref="#/components/schemas/containerStatus"),
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No Container for the id found or no StatusCheck for Container found",
     *      ),
     * )
     */
    public function getStatusCheckContainer($containerId){
        $container = $this->getDoctrine()->getRepository(Container::class)->find($containerId);

        if (!$container) {
            throw new ElementNotFoundException(
                'No Container for ID '.$containerId.' found'
            );
        }

        $containerStatus = $container->getStatus();

        if (!$containerStatus) {
            throw new ElementNotFoundException(
                'No StatusCheck for Container with ID '.$containerId.' found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($containerStatus, 'json');
        return new Response($response);
    }

    /**
     * Configure a StatusCheck for Container
     *
     * @Route("/monitoring/checks/containers/{containerId}", name="configure_status_check_container", methods={"PUT"})
     * @param $containerId
     * @param Request $request
     * @return Response
     * @throws ElementNotFoundException
     *
     * @OAS\Put(path="/monitoring/checks/containers/{containerId}",
     *     tags={"monitoring"},
     *     @OAS\Parameter(
     *      description="ID of the Container",
     *      in="path",
     *      name="containerId",
     *      required=true,
     *          @OAS\Schema(
     *              type="integer"
     *          ),
     *      ),
     *     @OAS\Parameter(
     *      description="Json-Object with attribute healthCheckEnabled which should be true or false",
     *      in="body",
     *      name="body",
     *      required=true,
     *      ),
     *      @OAS\Response(
     *          response=200,
     *          description="Returns the ContainerStatus",
     *          @OAS\JsonContent(ref="#/components/schemas/containerStatus"),
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No Container for the id found or no StatusCheck for Container found",
     *      ),
     * )
     */
    public function configureStatusCheckForContainer($containerId, Request $request) {
        $container = $this->getDoctrine()->getRepository(Container::class)->find($containerId);

        if (!$container) {
            throw new ElementNotFoundException(
                'No Container for ID '.$containerId.' found'
            );
        }

        $em = $this->getDoctrine()->getManager();

        $containerStatus = $container->getStatus();
        if(!$containerStatus){
            $containerStatus = new ContainerStatus();
        }

        if($request->request->get('healthCheckEnabled')) {
            $containerStatus->setHealthCheckEnabled(true);
            $container->setStatus($containerStatus);
            $em->persist($containerStatus);
            $em->persist($container);
            $em->flush();

            $serializer = $this->get('jms_serializer');
            $response = $serializer->serialize($containerStatus, 'json');
            return new Response($response);
        }

        $containerStatus->setHealthCheckEnabled(false);
        $container->setStatus($containerStatus);
        $em->persist($containerStatus);
        $em->persist($container);
        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($containerStatus, 'json');
        return new Response($response);
    }

    /**
     * @param String $logfileUrl
     * @return null|string|string[]
     */
    private function parseLogfileUrlToLogfileName(String $logfileUrl){
        return preg_replace('"\/1.0\/containers\/.*\/logs\/"', '', $logfileUrl);
    }
}