<?php
/**
 * @file
 * Contains Drupal\brickset_connect\src\BrickSet.
 */
namespace Drupal\brickset_connect;

use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;

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
		if (!$this->set_number) {
			throw new \Exception("BrickSet not initialized: no set number.");
		} elseif ($this->brick_set_exists()) {
			throw new \Exception("Brick Set already exists: " . $this->set_number);
		}

		$node = Node::create(['type' => 'brick_set']);
		$node->set('title', $this->set_number);

		if ($this->set_name) {
			$node->set('field_brick_set_name', $this->set_name);
		}

		if ($this->year_released) { 
			$node->set('field_brick_set_year_released', $this->year_released . '-01-01');
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

			$node->set('field_brick_set_image', $image_entities);
		}
		
		$node->status = 1;
		$node->enforceIsNew();
		$node->save();
	}

	private function brick_set_exists() {
		$values = \Drupal::entityQuery('node')->condition('title', $this->set_number)->execute();
		$node_exists = !empty($values);

		return !empty($node_exists);
	}
}