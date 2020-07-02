<?php
namespace Inwebium\Module\Model;

class Product implements \JsonSerializable
{
	private $id;
	private $name;
	private $isAvailable;
	private $price;
	private $manufacturer;

	public function __construct($id, $name, $isAvailable, $price, $manufacturer)
	{
        $this->setId($id)
            ->setName($name)
            ->setIsAvailable($isAvailable)
            ->setPrice($price)
            ->setManufacturer($manufacturer);
	}

	public function getId()
	{
		return $this->id;
	}

	public function setId($value)
	{
		$this->id = $value;
		return $this;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($value)
	{
		$this->name = $value;
		return $this;
	}

	public function getIsAvailable()
	{
		return $this->isAvailable;
	}

	public function setIsAvailable($value)
	{
		$this->isAvailable = $value;
		return $this;
	}

	public function getPrice()
	{
		return $this->price;
	}

	public function setPrice($value)
	{
		$this->price = $value;
		return $this;
	}

	public function getManufacturer()
	{
		return $this->manufacturer;
	}

	public function setManufacturer($value)
	{
		$this->manufacturer = $value;
		return $this;
	}

    public function jsonSerialize() {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'isAvailable' => $this->getIsAvailable(),
            'price' => $this->getPrice(),
            'manufacturer' => $this->getManufacturer()
        ];
    }

}