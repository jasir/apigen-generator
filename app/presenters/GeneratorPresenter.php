<?php

/**
 * Downloads and generates API doc
 *
 * Requires logged-in user
 *
 * @author Jan Dolecek <juzna.cz@gmail.com>
 */
class GeneratorPresenter extends BasePresenter {
	protected function startup() {
		parent::startup();

		// User must be authenticated
		if (PHP_SAPI !== 'cli' && !$this->getUser()->loggedIn) {
			$this->redirect('Sign:in');
		}
	}

	// print out all repos
	public function renderDefault()	{
		$this->template->repos = $this->db->table('repo')->order('name');
	}

	// generate API for all repos
	public function actionGenerateAll() {
		$this->beginRawOutput();
		foreach($this->db->query("select * from repo where lastPull is null or lastPull < date_sub(now(), interval 1 hour)") as $repo) {
			echo "Processing repo $repo->url ($repo->id)\n";
			$this->make($repo);
			echo '<hr>';
		}

		$this->sendResponse(new \Nette\Application\Responses\TextResponse("All done!"));
	}

	// regenerate all
	public function actionRegenerateAll() {
		$this->beginRawOutput();
		foreach($this->db->query("select * from repo") as $repo) {
			echo "Processing repo $repo->url ($repo->id)\n";
			$this->make($repo);
			echo '<hr>';
		}

		$this->sendResponse(new \Nette\Application\Responses\TextResponse("All done!"));
	}

	// generate api for one repo (by id or directory name)
	public function actionGenerate($dir) {
		$this->beginRawOutput();
		$repo = $this->db->query("select * from repo where id = ? or dir = ?", $dir, $dir)->fetch();
		if(!$repo) throw new \Nette\Application\BadRequestException("Requested repo doesnt exist");

		$this->make($repo);
		$this->sendResponse(new \Nette\Application\Responses\TextResponse("Done!"));
	}


	/**
	 * For actions which take long time: disable output buffer and close session.
	 */
	protected function beginRawOutput() {
		$this->session->close(); // we ain't want session to block it all

		// do not buffer!
		while(ob_get_level() > 0) ob_end_flush();
		ob_implicit_flush(true);

		echo '<pre>';
	}


	/**
	 * make it all
	 * @param \Nette\Database\Row $item
	 */
	protected function make($item) {
		$this->context->generator->make($item, (bool) $this->getParameter('force'));
	}
}
