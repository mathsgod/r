<?

namespace R;

class Set extends \ArrayIterator
{
	public static function Create($data, $delimiter = ",")
	{
		if ($data instanceof Set)
			return $data;

		if (is_string($data)) {
			$set = new Set();
			foreach (explode($delimiter, $data) as $d) {
				if (trim($d) == "")
					continue;
				$set[] = trim($d);
			}
		} elseif (is_array($data)) {
			$set = new Set($data);
		}

		return $set;
	}

	public function isSubsetOf($set)
	{
		$set = Set::Create($set);
		$a = $this->getArrayCopy();
		$b = $set->getArrayCopy();
		return array_intersect($a, $b) == $a;
	}


	public function union($set)
	{
		$a = $this->getArrayCopy();
		$b = Set::Create($set)->getArrayCopy();
		return Set::Create($a + $b);
	}

	public function intersection($set)
	{
		$a = $this->getArrayCopy();
		$b = Set::Create($set)->getArrayCopy();
		return Set::Create(array_intersect($a, $b));
	}

	public function different($set)
	{
		$a = $this->getArrayCopy();
		$b = Set::Create($set)->getArrayCopy();
		return Set::Create(array_diff($a, $b));
	}

	public function symmetricDifferent($set)
	{
		$set = Set::Create($set);
		$a = $set->different($this);
		$b = $this->different($set);
		return $a->union($b);
	}

	public function product($b)
	{
		$b = Set::Create($b);
		$r = new Set();
		foreach ($this as $i) {
			foreach ($b as $j) {
				$r[] = [$i, $j];
			}
		}
		return $r;
	}

	public function isEmpty()
	{
		return count($this) == 0;
	}

	public function powerSet()
	{
		$set = new Set();
		if ($this->isEmpty()) {
			$set[] = new Set();
			return $set;
		}

		foreach ($this as $i) {
			$subset = $this->different($i);
			foreach ($subset->powerSet() as $a) {
				$set[] = $a;
			}
		}
		return $set;

	}

}
