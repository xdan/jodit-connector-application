# Changelog

> **Tags:**
>
> - :boom: [Breaking Change]
> - :rocket: [New Feature]
> - :bug: [Bug Fix]
> - :memo: [Documentation]
> - :house: [Internal]
> - :nail_care: [Polish]

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
