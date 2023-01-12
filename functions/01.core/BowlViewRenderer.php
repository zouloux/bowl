<?php

// FIXME : To do when needed to render twig without Nano responders

/*
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class BowlViewRenderer
{
	protected FilesystemLoader $_loader;
	protected Environment $_environment;

	// Template root path, relative to app root.
	protected string $_templateRootPath;

	public function __construct ( string $templateRootPath ) {
		$this->_templateRootPath = $templateRootPath;
		$this->_loader = new FilesystemLoader( $this->_templateRootPath );
		$this->_environment = new Environment( $this->_loader );
	}

	function renderBowlPost ( BowlPost $bowlPost = null, array $vars = [] ):string {
		$templateName = (
			is_null($bowlPost) ? 'not-found' : $bowlPost->template
		);
		return $this->renderTemplate( $templateName, [
			// TODO : Global variables
			'post' => $bowlPost,
			...$vars,
		]);
	}

	function renderTemplate ( string $templateName, array $vars = [] ):string {
		$template = $this->_environment->load( $templateName.'.twig');
		$stream = $template->render( $vars );
		// TODO : Filter stream
		return $stream;
	}
}*/