<?php
/**
 * @file
 * Contains Drupal\brickset_connect\src\BrickSet.
 */
namespace Drupal\brickset_connect;

use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Exception;

class BrickSet {
	public $set_number = null;
	public $set_name = null;
	public $year_released = null;
	public $images = null;
	public $status = null;

	public function __construct($set_number, $set_name, $year_released, $images, $status = null) {
		$this->set_number = $set_number;
		$this->set_name = $set_name;
		$this->year_released = $year_released;
		$this->images = $images;
		$this->status = $status;
	}

	public function create_node() {
		/*
// Logs a notice
\Drupal::logger('brickset_connect')->notice($message);
// Logs an error
\Drupal::logger('brickset_connect')->error($message);
		*/
		try {
			\Drupal::logger('brickset_connect')->notice('About to run test_brick_set on ' . $this->set_number);
			$this->test_brick_set();
		
			$node = Node::create(['type' => 'brick_set']);
			$node = $this->brick_set_field_setup($node);
			
			$node->status = 1;
			$node->enforceIsNew();
			$node->save();
		}
		catch (Exception $e) {
			\Drupal::logger('brickset_connect')->error('create_node method failed.');
			drupal_set_message($e->getMessage());
		}
	}

	public function brick_set_entity_presave($entity) {
		$this->test_brick_set();

		$entity = $this->brick_set_field_setup($entity);

		return $entity;
	}

	private function brick_set_field_setup($entity) {
		$entity->set('title', $this->set_number);

		if ($this->set_name) {
			$entity->set('field_brick_set_name', $this->set_name);
		}

		if ($this->year_released) { 
			$entity->set('field_brick_set_year_released', $this->year_released . '-01-01');
		}
		
		if (count($this->images) > 0) {
			$image_entities = array();
			$i = 1;

			foreach($this->images as $image) {
				$ext = pathinfo($image->imageURL, PATHINFO_EXTENSION);
				$data = file_get_contents($image->imageURL);
				$file = file_save_data($data, 'public://' . $this->set_number . '-' . $i . '.' . $ext, FILE_EXISTS_REPLACE);
				$image_entities[$i] = ['target_id' => $file->id()];
				$i++;
			}

			$entity->set('field_brick_set_image', $image_entities);
		}

		return $entity;
	}

	private function brick_set_exists() {
		$values = \Drupal::entityQuery('node')->condition('title', $this->set_number)->execute();
		$node_exists = !empty($values);

		return !empty($node_exists);
	}

	public function test_brick_set() {
		if (!$this->set_number) {
			\Drupal::logger('brickset_connect')->error('Set number missing.');
			throw new Exception("BrickSet not initialized: no set number.");
		} elseif ($this->brick_set_exists()) {
			\Drupal::logger('brickset_connect')->error('Set number already exists.');
			throw new Exception("Brick Set already exists: " . $this->set_number);
		}
	}
}