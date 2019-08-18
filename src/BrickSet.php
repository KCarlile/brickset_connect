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
	public $theme = null;
	public $subtheme = null;
	public $images = null;
	public $status = null;

	public function __construct($set_number, $set_name, $year_released, $theme, $subtheme, $images, $status = null) {
		$this->set_number = $set_number;
		$this->set_name = $set_name;
		$this->year_released = $year_released;
		$this->theme = $theme;
		$this->subtheme = $subtheme;
		$this->images = $images;
		$this->status = $status;
	}

	public function create_node() {
		try {
			$this->test_brick_set();
		
			$node = Node::create(['type' => 'brick_set']);
			$node = $this->brick_set_field_setup($node);
			
			$node->status = 1;
			$node->enforceIsNew();
			$node->save();

			\Drupal::logger('brickset_connect')->notice('Created set #' . $this->set_number);
			drupal_set_message("Created set #" . $this->set_number);
		}
		catch (Exception $e) {
			\Drupal::logger('brickset_connect')->error('create_node method failed.');
			drupal_set_message($e->getMessage());
		}
	}

	private function brick_set_field_setup($entity) {
		$entity->set('title', $this->set_number);

		if ($this->set_name) {
			$entity->set('field_brick_set_name', $this->set_name);
		}

		if ($this->year_released) { 
			$entity->set('field_brick_set_year_released', $this->year_released . '-01-01');
		}

		if ($this->theme) {
			$entity->set('field_brick_set_theme', $this->theme);
		}

		if ($this->subtheme) {
			$entity->set('field_brick_set_subtheme', $this->subtheme);
		}

		\Drupal::logger('brickset_connect')->notice('brick_set_field_setup: ' . print_r($this->images,true));
		
		if ($this->images) {
			$image_entities = array();
			$i = 1;

			foreach($this->images as $image) {
				\Drupal::logger('brickset_connect')->notice('Saving image: ' . $image);
				$ext = pathinfo($image, PATHINFO_EXTENSION);
				$data = file_get_contents($image);
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