<?php
namespace Trois\Utils\Dto;

use Psr\Http\Message\ServerRequestInterface;
use Cake\Http\Exception\BadRequestException;

trait DtoTrait {

  protected function dtoParse(ServerRequestInterface $request, $dtoClass, $key = null):array | BadRequestException
  {
    try {
      $mapped = (new \CuyZ\Valinor\MapperBuilder())
      ->mapper()
      ->map($dtoClass, $request->getData($key));
    } catch (\CuyZ\Valinor\Mapper\MappingError $error) {
      return new BadRequestException($error->getMessage());
    }

    return json_decode(json_encode($mapped), true);
  }
}