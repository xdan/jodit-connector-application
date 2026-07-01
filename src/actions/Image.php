<?php
declare(strict_types=1);

namespace Jodit\actions;

use claviska\SimpleImage;
use Jodit\components\Config;
use Jodit\Consts;
use Jodit\Helper;
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
	public function actionImageResize(): array {
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

		return [
			'newPath' => $source->baseurl . $info->newname
		];
	}

	/**
	 * @throws Exception
	 */
	public function actionImageCrop(): array {
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

		return [
			'newPath' => $source->baseurl . $info->newname
		];
	}

	/**
	 * Save a client-side edited image.
	 *
	 * Unlike {@see actionImageResize()} / {@see actionImageCrop()} — which
	 * re-process an existing server file from geometric box parameters — this
	 * accepts the final edited image bytes (crop, filters, finetune and
	 * annotations already baked in) as an uploaded multipart file and writes
	 * them. The target is `newname` ("save as") when provided, otherwise the
	 * original `name` is overwritten in place. Used by the client-side image
	 * editor.
	 *
	 * @throws Exception
	 */
	public function actionImageSave(): array {
		$source = $this->config->getSource($this->request->source);

		$this->config->access->checkPermission(
			$this->config->getUserRole(),
			$this->action,
			$source->getPath()
		);

		if (!isset($_FILES[$source->defaultFilesKey])) {
			throw new Exception(
				'No image has been uploaded',
				Consts::ERROR_CODE_NO_FILES_UPLOADED
			);
		}

		$files = $_FILES[$source->defaultFilesKey];

		$tmpName = is_array($files['tmp_name'])
			? ($files['tmp_name'][0] ?? '')
			: $files['tmp_name'];

		$error = is_array($files['error'])
			? ($files['error'][0] ?? UPLOAD_ERR_NO_FILE)
			: $files['error'];

		if ($error || !$tmpName || !is_uploaded_file($tmpName)) {
			throw new Exception(
				'No image has been uploaded',
				Consts::ERROR_CODE_NO_FILES_UPLOADED
			);
		}

		$path = $source->getPath();

		$origName = $this->request->name
			? Helper::makeSafe((string) $this->request->name)
			: '';

		$newName = $this->request->newname
			? Helper::makeSafe((string) $this->request->newname)
			: '';

		$target = $newName ?: $origName;

		if (!$target) {
			throw new Exception(
				'Either "name" or "newname" is required',
				Consts::ERROR_CODE_BAD_REQUEST
			);
		}

		// Validate the incoming bytes really are a decodable image before
		// touching the filesystem — never trust the client blob.
		try {
			$img = new SimpleImage();
			$img->fromFile($tmpName);
		} catch (Exception $e) {
			throw new Exception(
				'Provided data is not a valid image',
				Consts::ERROR_CODE_BAD_REQUEST
			);
		}

		// Ensure the target keeps an image extension.
		if (!pathinfo($target, PATHINFO_EXTENSION)) {
			$ext = $origName ? pathinfo($origName, PATHINFO_EXTENSION) : '';

			if (!$ext) {
				$ext = str_replace('image/', '', (string) $img->getMimeType());
				$ext = $ext === 'jpeg' ? 'jpg' : $ext;
			}

			$target = $target . '.' . $ext;
		}

		$img->toFile(
			$path . $target,
			$img->getMimeType(),
			$source->quality
		);

		$file = $source->makeFile($path . $target);

		if (!$file->isSafeFile($source)) {
			$file->remove();
			throw new Exception(
				'File type is not in white list',
				Consts::ERROR_CODE_FORBIDDEN
			);
		}

		return [
			'newPath' => $source->baseurl . $target
		];
	}

	/**
	 * Return an image file as a base64 data URL.
	 *
	 * The raw file host often serves images without CORS headers, so a browser
	 * on a different origin (a dev server, the image editor) can't fetch them
	 * directly. This returns the bytes through the connector's own CORS-enabled
	 * JSON API instead — the same path the file browser already uses.
	 *
	 * @throws Exception
	 */
	public function actionImageLoad(): array {
		$source = $this->config->getSource($this->request->source);

		$this->config->access->checkPermission(
			$this->config->getUserRole(),
			$this->action,
			$source->getPath()
		);

		$name = $this->request->name
			? Helper::makeSafe((string) $this->request->name)
			: '';

		if (!$name) {
			throw new Exception(
				'Name not specified',
				Consts::ERROR_CODE_BAD_REQUEST
			);
		}

		$path = $source->getPath();
		$file = $path . $name;

		if (!file_exists($file) || !is_file($file)) {
			throw new Exception(
				'File does not exist',
				Consts::ERROR_CODE_NOT_EXISTS
			);
		}

		$mimeMap = [
			'jpg' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png' => 'image/png',
			'gif' => 'image/gif',
			'webp' => 'image/webp',
			'svg' => 'image/svg+xml',
			'bmp' => 'image/bmp',
			'avif' => 'image/avif',
			'ico' => 'image/x-icon'
		];

		$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
		$mime = $mimeMap[$ext] ?? 'application/octet-stream';

		$content =
			'data:' .
			$mime .
			';base64,' .
			base64_encode((string) file_get_contents($file));

		return [
			'content' => $content,
			'name' => $name
		];
	}

	abstract function getImageEditorInfo(): ImageInfo;
}
