<?php // lint >= 8.2

class MixedPromotion
{

	public function __construct(
		private readonly int $id,
		private string $name,
	) {
	}

}

readonly class ValidReadonly
{

	public function __construct(
		private int $id,
		private string $name,
	) {
	}

}

class WithoutPromotion
{

	private readonly int $id;

	public function __construct(int $id)
	{
		$this->id = $id;
	}

}
