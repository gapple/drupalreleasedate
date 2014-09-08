<?php
namespace DrupalReleaseDate\NumberGenerator;

abstract class AbstractWeighted extends AbstractGenerator
{
    /**
     * An array of cumulative weights for each possible outcome of the
     * generator.
     *
     * e.g.
     *   if two items have 50% chance:
     *     array(1 => 1, 2 => 2)
     *   if two items have a 25% and 75% chance:
     *     array(1 => 1, 2 => 4)
     *     or
     *     array(1 => 3, 2 => 4)
     *
     * @var array
     */
    protected $weightsArray = array();

    /**
     * @var NumberGeneratorInterface
     */
    protected $weightGenerator = null;

    public function __construct(NumberGeneratorInterface $weightGenerator, $min, $max)
    {
        parent::__construct($min, $max);

        $this->weightGenerator = $weightGenerator;

        $this->evaluateWeights();
    }

    /**
     * Evaluate and store weights for the range currently set.
     */
    protected function evaluateWeights() {

        $cumulativeWeight = 0;
        if (!empty($this->weightsArray)) {
            $cumulativeWeight = end($this->weightsArray);
        }

        $weightType = NumberGeneratorInterface::TYPE_INT;

        $weightsNeeded = $this->max - $this->min + 1;

        for ($i = count($this->weightsArray); $i < $weightsNeeded; $i++) {
            $weight = $this->calculateWeight($i);
            if ($weight < 0) {
                throw new \RangeException('The value at index ' . $i . ' was given a weight of ' . $weight);
            }
            $cumulativeWeight += $weight;
            $this->weightsArray[$i] = $cumulativeWeight;

            if (!is_int($cumulativeWeight)) {
                $weightType = NumberGeneratorInterface::TYPE_FLOAT;
            }
        }

        $this->weightGenerator->setType($weightType);

        $this->weightGenerator->setMax($this->weightsArray[$this->max - $this->min]);

        if ($weightType == NumberGeneratorInterface::TYPE_FLOAT) {
            $this->weightGenerator->setMin(0);
        }
        else {
            $this->weightGenerator->setMin(1);
        }
    }

    /**
     * Set a new minimum value for the generator.
     *
     * @see \DrupalReleaseDate\NumberGenerator\AbstractGenerator::setMin()
     */
    public function setMin($min)
    {
        parent::setMin($min);

        $this->evaluateWeights();
    }

    /**
     * Set a new maximum value for the generator.
     *
     * @see \DrupalReleaseDate\NumberGenerator\AbstractGenerator::setMax()
     */
    public function setMax($max)
    {
        parent::setMax($max);

        $this->evaluateWeights();
    }

    /**
     * Calculate the weight of a value provided by the generator.
     *
     * @param int $index
     * @return int
     */
    abstract public function calculateWeight($index);

    public function generate()
    {
        $weight = $this->weightGenerator->generate();

        // Find the first weight that the number fits in to.
        $index = 0;
        foreach ($this->weightsArray as $index => $weightBound) {
            if ($weight <= $weightBound) {
                break;
            }
        }
        return $index + $this->min;
    }
}
