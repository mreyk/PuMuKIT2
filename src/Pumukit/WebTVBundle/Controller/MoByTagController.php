<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Tag;

class MoByTagController extends Controller
{
    /**
     * @Route("/multimediaobjects/tag/{id}")
     * @Template()
     */
    public function indexAction(Tag $tag, Request $request)
    {
    	$repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');

        dump($tag);
        dump($request->getLocale());

        /*if($sort == "alphabetically"){
    		$mmobjs = $repo->findWithTag($tag, array('alphabetically.' . $request->getLocale() => +1));
        }
        else{*/
        	$mmobjs = $repo->findWithTag($tag, array('record_date' => +1));
        	dump($mmobjs);
       // }

        dump($mmobjs);

        return array('mmobjs' => $mmobjs, 'tag' => $tag);
    }
}