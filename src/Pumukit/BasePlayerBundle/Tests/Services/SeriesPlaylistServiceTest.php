<?php

namespace Pumukit\BasePlayerBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SeriesPlaylistServiceTest extends WebTestCase
{
    private $dm;
    private $mmobjRepo;
    private $seriesRepo;
    private $seriesPlaylistService;
    private $testMmobjs;

    public function setUp()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);
        $container = static::$kernel->getContainer();
        $this->dm = $container->get('doctrine_mongodb')->getManager();
        $this->mmobjRepo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->seriesRepo = $this->dm->getRepository('PumukitSchemaBundle:Series');
        $this->seriesPlaylistService = $container->get('pumukit_baseplayer.seriesplaylist');

        $track = new Track();
        $series = new Series();
        $series2 = new Series();
        $mmobjs = array(
            'published' => new MultimediaObject(),
            'hidden' => new MultimediaObject(),
            'blocked' => new MultimediaObject(),
            'prototype' => new MultimediaObject(),
        );
        $mmobjs['published']->setStatus(MultimediaObject::STATUS_PUBLISHED);
        $mmobjs['blocked']->setStatus(MultimediaObject::STATUS_BLOCKED);
        $mmobjs['hidden']->setStatus(MultimediaObject::STATUS_HIDDEN);
        $mmobjs['prototype']->setStatus(MultimediaObject::STATUS_PROTOTYPE);
        $playlistMmobjs = array(
            'published' => new MultimediaObject(),
        );
        $track->setUrl('funnyurl.mp4');
        $playlistMmobjs['published']->setStatus(MultimediaObject::STATUS_PUBLISHED);

        foreach ($mmobjs as $mmobj) {
            $mmobj->setSeries($series);
            $mmobj->addTrack($track);
            $this->dm->persist($mmobj);
        }
        $this->dm->persist($series);
        $this->dm->persist($series2);
        $this->dm->flush();
        foreach ($playlistMmobjs as $mmobj) {
            $mmobj->addTrack($track);
            $mmobj->setSeries($series2);
            $this->dm->persist($mmobj);
            $series->getPlaylist()->addMultimediaObject($mmobj);
        }
        $this->dm->persist($series);
        $this->dm->flush();
        $this->dm->clear();

        foreach ($mmobjs as $key => $mmobj) {
            $mmobjs[$key] = $this->mmobjRepo->find($mmobj->getId());
        }
        foreach ($playlistMmobjs as $key => $mmobj) {
            $playlistMmobjs[$key] = $this->mmobjRepo->find($mmobj->getId());
        }
        $series = $this->seriesRepo->find($series->getId());

        $this->testMmobjs = $mmobjs;
        $this->testPlaylistMmobjs = $playlistMmobjs;
        $this->testSeries = $series;
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->mmobjRepo = null;
        $this->seriesPlaylistService = null;
        $this->testMmobjs = null;
        $this->testPlaylistMmobjs = null;
        $this->testSeries = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testGetPlaylistMmobjs()
    {
        $playlistMmobjs = $this->seriesPlaylistService->getPlaylistMmobjs($this->testSeries);
        $this->assertEquals(array(
            $this->testMmobjs['published'],
            $this->testMmobjs['hidden'],
            $this->testMmobjs['blocked'],
            $this->testPlaylistMmobjs['published'],
        ), iterator_to_array($playlistMmobjs, false));
    }

    public function testGetPlaylistFirstMmobj()
    {
        $playlistMmobj = $this->seriesPlaylistService->getPlaylistFirstMmobj($this->testSeries);
        $this->assertEquals($this->testMmobjs['published'], $playlistMmobj);
    }

    public function testGetMmobjFromIdAndPlaylist()
    {
        $playlistMmobj = $this->seriesPlaylistService->getMmobjFromIdAndPlaylist($this->testMmobjs['published']->getId(), $this->testSeries);
        $this->assertEquals($this->testMmobjs['published'], $playlistMmobj);
    }
}
