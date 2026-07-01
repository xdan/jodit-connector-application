# Changelog

> **Tags:**
>
> - :boom: [Breaking Change]
> - :rocket: [New Feature]
> - :bug: [Bug Fix]
> - :memo: [Documentation]
> - :house: [Internal]
> - :nail_care: [Polish]

## 3.0.54

#### :lock: Security

- `fileUploadRemote` no longer follows redirects blindly: redirects are resolved
  manually and **each hop is re-validated** against the SSRF guard, so a public
  URL that 302-redirects to an internal address (e.g. `169.254.169.254`) is
  blocked too.

## 3.0.53

#### :lock: Security

- SSRF protection for `fileUploadRemote`: the `url` must be `http`/`https`, and
  its host must not resolve to a loopback / private / link-local / reserved
  address (`127.0.0.0/8`, `10/8`, `172.16/12`, `192.168/16`, `169.254/16`, `::1`,
  `fc00::/7`, `localhost`, …). Hostnames are resolved so a name pointing at an
  internal IP is caught too. Set `allowPrivateNetworkUploads => true` in the
  config to permit private hosts on a trusted internal setup.

## 3.0.52

#### :lock: Security

- `imageSave` and `imageLoad` now require a **POST** request; a `GET` is rejected
  with `406`. This prevents the read-only `imageLoad` from being embedded (via a
  cached/logged `GET`) and abused as an open file proxy.

## 3.0.51

#### :rocket: New Feature

- Add `imageLoad` action. It returns an image file as a base64 **data URL**
  through the CORS-enabled JSON API, so a browser on a different origin (a dev
  server, the image editor) can read a file the raw file host would otherwise
  block via CORS.

  Request:
  - `action=imageLoad`
  - `source` — source name
  - `path` — optional directory within the source
  - `name` — image file name

  Response:

  ```json
  {
  	"success": true,
  	"data": {
  		"code": 220,
  		"content": "data:image/jpeg;base64,…",
  		"name": "photo.jpg"
  	}
  }
  ```

## 3.0.50

#### :rocket: New Feature

- Add `imageSave` action. It receives a client-side edited image (crop, filters,
  finetune and annotations already baked into the bytes) as an uploaded
  multipart file and writes it, then returns the new public URL — mirroring the
  `imageResize`/`imageCrop` response. Used by the client-side image editor.

  Request (multipart/form-data):
  - `action=imageSave`
  - `source` — source name
  - `path` — optional directory within the source
  - `name` — original file name; overwritten in place when `newname` is omitted
  - `newname` — optional target file name ("save as")
  - `files` — the edited image bytes (multipart file field)

  Response:

  ```json
  {
  	"success": true,
  	"data": {
  		"code": 220,
  		"newPath": "http://localhost:8081/files/photo-edited.png"
  	}
  }
  ```

#### :bug: Bug Fix

- PHP 8.5 compatibility: the global error handler no longer escalates
  deprecation notices (`E_DEPRECATED` / `E_USER_DEPRECATED`) to a fatal `501`.
  Newer PHP deprecates functions still used by vendored libraries (e.g.
  SimpleImage's `imagedestroy()`), which previously broke image operations.

## 3.0.42

#### :boom: Breaking Change

- PHP 8

#### :internal: Internal

- Update dependencies

```
"abeautifulsite/simpleimage": "4.2.1"
"dompdf/dompdf": "3.0.0"
"cocur/slugify": "4.6.0"
```

### :rocket: New Feature

- Add `pdf` options to `config.php`

```php
"pdf" => [
	"defaultFont" => "serif",
	"isRemoteEnabled" => true,
	"paper" => [
		'format' => 'A4',
		'page_orientation' => 'portrait'
	]
]
```

## 3.0.1

#### :boom: Breaking Change

Change response for folders

There was:

```json
{
	"success": true,
	"data": {
		"sources": {
			"source1": {
				"folders": []
			},
			"source2": {
				"folders": []
			}
		}
	}
}
```

Now:

```json
{
	"success": true,
	"data": {
		"sources": [
			{
				"name": "source1",
				"folders": []
			},
			{
				"name": "source2",
				"folders": []
			}
		]
	}
}
```
