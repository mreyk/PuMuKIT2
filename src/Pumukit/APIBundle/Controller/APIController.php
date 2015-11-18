<?php

namespace Pumukit\APIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/api/media")
 */
class APIController extends Controller
{
    /**
     * @Route("/stats.{_format}", defaults={"_format"="json"}, requirements={"_format": "json"})
     */
    public function statsAction()
    {
        $mmRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $seriesRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:series');
        $liveRepo = $this->get('doctrine_mongodb')->getRepository('PumukitLiveBundle:Live');

        $totalSeries = $seriesRepo->countPublic();
        $totalMmobjs = $mmRepo->count();
        $totalHours = bcdiv($mmRepo->countDuration(), 3600, 2);
        $totalLiveChannels = $liveRepo->createQueryBuilder()
                                      ->count()
                                      ->getQuery()
                                      ->execute();

        $counts = array('series' => $totalSeries,
                        'mms' => $totalMmobjs,
                        'hours' => $totalHours, 
                        'live_channels' => $totalLiveChannels,);

        return new JsonResponse($counts);
    }

    /**
     * @Route("/mmobj.{_format}", defaults={"_format"="json"}, requirements={"_format": "json"})
     */
    public function multimediaObjectsAction(Request $request)
    {
        $mmRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $serializer = $this->get('serializer');
        
        $limit = $request->get('limit');
        $page = $request->get('page');
        $skip = $request->get('skip');
        $criteria = $request->get('criteria')?:array();
        $sort = $request->get('sort')?:array();
        $prototypes = $request->get('prototypes')?:false;

        if(!$limit || $limit > 100 ) {
            $limit = 100;
        }
        if($page && $page > 0) {
            $limit = $limit?:10;
            $skip = $limit*($page-1);
        }
        else {
            $page = null;
        }

        if($prototypes) {
            $qb = $mmRepo->createQueryBuilder();
        }
        else {
            $qb = $mmRepo->createStandardQueryBuilder();
        }

        if($criteria){
            $qb = $qb->addAnd($criteria);
        }

        $qb_mmobjs = clone $qb;
        $qb_mmobjs = $qb_mmobjs->limit($limit)
                               ->skip($skip)
                               ->sort($sort);

        $total = $qb->count()->getQuery()->execute();        
        $mmobjs = $qb_mmobjs->getQuery()->execute()->toArray();

        $counts = array('total' => $total, 
                        'limit' => $limit,
                        'page' => $page ,
                        'criteria' => $criteria, 
                        'sort'  => $sort,
                        'mmobjs' => $mmobjs);

        $data = $serializer->serialize($counts, 'json');

        return new Response($data, 200, array('Content-Type' =>'application/json'));
    }

    /**
     * @Route("/series.{_format}", defaults={"_format"="json"}, requirements={"_format": "json"})
     */
    public function seriesAction(Request $request)
    {
        $seriesRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');
        $serializer = $this->get('serializer');
        $limit = $request->get('limit');
        $page = $request->get('page');
        $skip = $request->get('skip');
        $criteria = $request->get('criteria')?:array();
        $sort = $request->get('sort')?:array();
        $prototypes = $request->get('prototypes')?:false;

        if(!$limit || $limit > 100 ) {
            $limit = 100;
        }
        if($page && $page > 0) {
            $limit = $limit?:10;
            $skip = $limit*($page-1);
        }
        else {
            $page = null;
        }

        $qb = $seriesRepo->createQueryBuilder();

        if($criteria){
            $qb = $qb->addAnd($criteria);
        }

        $qb_series = clone $qb;
        $qb_series = $qb_series->limit($limit)
                               ->skip($skip)
                               ->sort($sort);

        $total = $qb->count()->getQuery()->execute();        
        $series = $qb_series->getQuery()->execute()->toArray();

        $counts = array('total' => $total, 
                        'limit' => $limit,
                        'page' => $page ,
                        'criteria' => $criteria, 
                        'sort'  => $sort,
                        'series' => $series,);

        $data = $serializer->serialize($counts, 'json');

        return new Response($data, 200, array('Content-Type' =>'application/json'));
    }

    /**
     * @Route("/live.{_format}", defaults={"_format"="json"}, requirements={"_format": "json"})
     */
    public function liveAction(Request $request)
    {
        $liveRepo = $this->get('doctrine_mongodb')->getRepository('PumukitLiveBundle:Live');
        $serializer = $this->get('serializer');

        $limit = $request->get('limit');
        $criteria = $request->get('criteria')?:array();
        $sort = $request->get('sort')?:array();

        $qb = $liveRepo->createQueryBuilder();

        if($criteria){
            $qb = $qb->addAnd($criteria);
        }

        $qb_series = clone $qb;
        $qb_series = $qb_series->limit($limit)
                               ->sort($sort);

        $qb_live = clone $qb;

        $total = $qb->count()->getQuery()->execute();
        $live = $qb_live->getQuery()->execute()->toArray();

        $counts = array('total' => $total, 
                        'limit' => $limit,
                        'criteria' => $criteria, 
                        'sort'  => $sort,
                        'live' => $live);

        $data = $serializer->serialize($counts, 'json');

        return new Response($data, 200, array('Content-Type' =>'application/json'));
    }

}

