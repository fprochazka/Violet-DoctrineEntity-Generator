<?php

use Nette\Application\UI\Form;


class EntitiesPresenter extends BasePresenter
{

	/** @persistent */
	public $_t;

	/** @var Nette\Http\SessionSection */
	private $session;



	protected function startup()
	{
		parent::startup();

		$this->session = $this->context->session->getSection('Kdyby.Violet/Class.Entities');

		if (!$this->_t || !isset($this->session[$this->_t])) {
			do {
				$this->_t = Nette\Utils\Strings::random(3);
			} while(isset($this->session[$this->_t]));

			$this->session[$this->_t] = (object)array(
				'uml' => NULL,
				'packages' => array(),
				'types' => array()
			);
		}
	}



	/**
	 * @return string|NULL
	 */
	public function getUml()
	{
		return $this->session[$this->_t]->uml;
	}



	/**
	 * @return array
	 */
	public function getPackages()
	{
		return $this->session[$this->_t]->packages;
	}



	/**
	 * @return array
	 */
	public function getTypes()
	{
		return $this->session[$this->_t]->types;
	}



	/**
	 * @return Form
	 */
	protected function createComponentUpload()
	{
		$form = new Form;
		$form->addUpload('uml', 'Class.Violet')
			->addRule(Form::MIME_TYPE, 'Soubor musí být XML', 'application/xml')
			->setRequired();

		$form->addSubmit('upload', 'Nahrát');
		$form->onSuccess[] = callback($this, 'UploadSubmitted');

		return $form;
	}



	/**
	 * @param Form $upload
	 */
	public function UploadSubmitted(Form $upload)
	{
		$this->session[$this->_t]->uml = $upload['uml']->value->contents;

		$reader = new Kdyby\Violet\ClassDiagramReader($this->getUml());
		$this->session[$this->_t]->packages = $reader->getPackages();
		$this->session[$this->_t]->types = $reader->getTypes();

		Nette\Diagnostics\Debugger::barDump($this->getPackages(), 'Packages');
		Nette\Diagnostics\Debugger::barDump($this->getTypes(), 'Types');

//		$this->redirect('list');
	}


	/*************** List ***************/


	public function renderList()
	{
		$this->template->packages = $this->getPackages();
		$this->template->types = $this->getTypes();
	}


	/*************** Generate ***************/



	public function renderGenerate($type)
	{

	}


}
