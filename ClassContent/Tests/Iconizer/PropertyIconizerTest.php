<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\ClassContent\Tests\Iconizer;

use BackBee\ClassContent\AbstractClassContent;
use BackBee\ClassContent\Element\Image;
use BackBee\ClassContent\Tests\Mock\MockContent;
use BackBee\Tests\BackBeeTestCase;
use BackBee\ClassContent\Iconizer\PropertyIconizer;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 */

class PropertyIconizerTest extends BackBeeTestCase {
    
    private $content;
    private $property;

    public function setUp()
    {
        $this->content = new MockContent();
        $this->content->load();
	$this->property = new PropertyIconizer(self::$app->getRouting());
    }
    
    public function testGetIcon(){

	$this->content	 
	    ->mockedDefineData(
		'imageTest',
		'BackBee\ClassContent\Element\Image',
		array()
	    )
	    ->mockedDefineData(
		'image',
		'BackBee\ClassContent\Element\Image',
		array()
	    );
	 $this->content
	     ->mockedDefineProperty('iconized-by', 'image')
//	     ->setProperty(
//		'iconized-by','image'
//	    )
	    ;

	$prop1 = $this->property->getIcon($this->content);
	$this->assertTrue($prop1 instanceof AbstractContent);	
	
	$this->content	 
	    ->mockedDefineData(
		'imageTest',
		'BackBee\ClassContent\Element\Image',
		array()
	    )
	    ->mockedDefineData(
		'image',
		'BackBee\ClassContent\Element\Image',
		array()
	    )
	    ->setProperty(
		'iconized-by','/resources/'
	    );

	$prop2 = $this->property->getIcon($this->content);
	$this->assertTrue($prop2 instanceof AbstractContent);

    }

    public function testParseProperty()
    {

	$this->content	 
	    ->mockedDefineData(
		'imageTest',
		'BackBee\ClassContent\Element\Image',
		array()
	    )
	    ->mockedDefineData(
		'image',
		'BackBee\ClassContent\Element\Image',
		array()
	    )
	    ->setProperty(
		'iconized-by','image'
	    );
	$prop1 = $this->invokeMethod($this->property, 'parseProperty', array($this->content, 'image'));
	$this->assertTrue($prop1 instanceof AbstractContent);	

    }


    public function testIconizeByParam()
    {

	$this->content->mockedDefineParam(
            'link',
            [
                'type' => 'linkSelector',
                'label'      => 'Link',
                'value'      =>  'val'	 
            ]
        );
	$prop1 = $this->invokeMethod($this->property, 'iconizeByParam', array($this->content, 'link'));
	$this->assertTrue($prop1 == '/val');
	
	
	$this->content->mockedDefineParam(
            'linkNull',
            [
                'type' => 'linkSelector',
                'label'      => 'Link',
                'value'      => null	 
            ]
        );
	$prop2 = $this->invokeMethod($this->property, 'iconizeByParam', array($this->content, 'linkNull'));
	$this->assertNull($prop2);
	
	$prop3 = $this->invokeMethod($this->property, 'iconizeByParam', array($this->content, 'linkTest'));
	$this->assertNull($prop3);

    }


    public function testIconizedByElement()
    {
	$this->content	 
	    ->mockedDefineData(
		'image',
		'BackBee\ClassContent\Element\Image',
		array()
	    )
	    ->mockedDefineData(
		'test_image',
		'BackBee\ClassContent\Element\Image',
		array()
	    )
	    ->setProperty(
		'iconized-by',
		 array(
			0 => array(
			    0 => 'image->path',
			),
		 )
	    );
	 $prop1 = $this->invokeMethod($this->property, 'iconizedByElement', array($this->content, 'image'));
	 $this->assertTrue(is_a($prop1, 'BackBee\ClassContent\Element\Image'));  
	 
	 $prop2 = $this->invokeMethod($this->property, 'iconizedByElement', array($this->content, 'test_image'));
	 $this->assertTrue(is_a($prop2, 'BackBee\ClassContent\Element\Image'));
	
	// $prop3 = $this->invokeMethod($this->property, 'iconizedByElement', array($this->content, 'test'));
	//  $this->assertNull($prop3);

   }
    
}