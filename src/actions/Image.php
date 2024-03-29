<?php
declare(strict_types=1);

namespace Jodit\actions;

use Jodit\components\Config;
use Jodit\Consts;
use Exception;
use Jodit\interfaces\ImageInfo;

/**
 * Trait Image
 * @package Jodit\actions
 */
trait Image {
	public Config $config;

	public string $action;

	/**
	 * Resize image
	 *
	 * @throws \Exception
	 */
	public function actionImageResize(): void {
		$source = $this->config->getSource($this->request->source);

		$this->config->access->checkPermission(
			$this->config->getUserRole(),
			$this->action,
			$source->getPath()
		);

		$info = $this->getImageEditorInfo();

		if (!$info->box || (int) $info->box->w <= 0) {
			throw new Exception(
				'Width not specified',
				Consts::ERROR_CODE_BAD_REQUEST
			);
		}

		if (!$info->box || (int) $info->box->h <= 0) {
			throw new Exception(
				'Height not specified',
				Consts::ERROR_CODE_BAD_REQUEST
			);
		}

		$info->img
			->resize((int) $info->box->w, (int) $info->box->h)
			->toFile(
				$info->path . $info->newname,
				$info->img->getMimeType(),
				$source->quality
			);
	}

	/**
	 * @throws Exception
	 */
	public function actionImageCrop(): void {
		$source = $this->config->getSource($this->request->source);

		$this->config->access->checkPermission(
			$this->config->getUserRole(),
			$this->action,
			$source->getPath()
		);

		$info = $this->getImageEditorInfo();

		if (
			(int) $info->box->x < 0 ||
			(int) $info->box->x > (int) $info->width
		) {
			throw new Exception(
				'Start X not specified',
				Consts::ERROR_CODE_BAD_REQUEST
			);
		}

		if (
			(int) $info->box->y < 0 ||
			(int) $info->box->y > (int) $info->height
		) {
			throw new Exception(
				'Start Y not specified',
				Consts::ERROR_CODE_BAD_REQUEST
			);
		}

		if ((int) $info->box->w <= 0) {
			throw new Exception(
				'Width not specified',
				Consts::ERROR_CODE_BAD_REQUEST
			);
		}

		if ((int) $info->box->h <= 0) {
			throw new Exception(
				'Height not specified',
				Consts::ERROR_CODE_BAD_REQUEST
			);
		}

		$info->img
			->crop(
				(int) $info->box->x,
				(int) $info->box->y,
				(int) $info->box->x + (int) $info->box->w,
				(int) $info->box->y + (int) $info->box->h
			)
			->toFile(
				$info->path . $info->newname,
				$info->img->getMimeType(),
				$source->quality
			);
	}

	abstract function getImageEditorInfo(): ImageInfo;
}
