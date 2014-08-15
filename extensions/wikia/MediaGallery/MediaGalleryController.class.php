<?php

class MediaGalleryController extends WikiaController {
	const DEFAULT_TEMPLATE_ENGINE = WikiaResponse::TEMPLATE_ENGINE_MUSTACHE;
	const MAX_ITEMS = 8;
	const MAX_EXPANDED_ITEMS = 100;

	public function gallery() {
		$items = $this->getVal( 'items' );
		$expanded = ( bool ) $this->getVal( 'expand', false ); //TODO: pass in expand

		$media = [];

		$itemCount = count( $items );

		$dimensionIndex = 0;
		foreach ( $items as $item ) {
			$file = wfFindFile( $item['title'] );
			$classes = [];

			if ( !$file instanceof File ) {
				continue; // todo: possible add error state
			}

			$dimension = MediaGalleryHelper::getDimensionBySizeAndOrder( $itemCount, $dimensionIndex );
			$dimensions = [
				'width' => $dimension,
				'height' => $dimension,
			];
			$thumb = $file->transform( $dimensions );
			$thumbUrl = wfReplaceImageServer(
				WikiaFileHelper::getSquaredThumbnailUrl( $file, $dimension ),
				$file->getTimestamp()
			);

			$params = [
				'thumb' => $thumb,
				'options' => [
					'custom-img-src' => $thumbUrl,
					'file-link' => $file->getUrl(),
					'fluid' => true,
				]
			];
			$markup = $this->app->renderView(
				'ThumbnailController',
				'image',
				$params
			);

			// Hide overflow items
			if ( $dimensionIndex >= self::MAX_ITEMS ) {
				$classes[] = "hidden";
				$classes[] = "fade";
			}

			$media[] = [
				'thumbnail' => $markup,
				'classes' => join( " ", $classes )
			];
			++$dimensionIndex;
		}

		$count = count( $media );
		if ( $count > self::MAX_ITEMS ) {
			$count = 'many';
		}

		$this->media = $media;
		$this->count = $count;
		$this->expanded = $expanded;
		$this->max = $expanded ? self::MAX_EXPANDED_ITEMS : self::MAX_ITEMS;
	}

}