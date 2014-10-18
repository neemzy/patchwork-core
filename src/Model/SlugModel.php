<?php

namespace Neemzy\Patchwork\Model;

trait SlugModel
{
    /**
     * Generates a slug for the model
     *
     * @return string
     */
    public function slugify()
    {
        return $this->vulgarize($this->__toString()) ?: $slug = $this->getTableName().'-'.$this->id;
    }



    /**
     * RedBean update method
     * Caches this model's slug into one of its fields
     *
     * @return void
     */
    protected function slugUpdate()
    {
        $this->slug = $this->slugify();
    }



    /**
     * Makes a string URL-compatible
     *
     * @param string $string String to transform
     *
     * @return string
     */
    private function vulgarize($string)
    {
        return trim(
            preg_replace(
                '/(-+)/',
                '-',
                preg_replace(
                    '/([^a-z0-9-]*)/',
                    '',
                    preg_replace(
                        '/((\s|\.|\'|\/)+)/',
                        '-',
                        html_entity_decode(
                            preg_replace(
                                '/&(a|o)elig;/',
                                '$1e',
                                preg_replace(
                                    '/&([a-z])(uml|acute|grave|circ|tilde|ring|cedil|slash);/',
                                    '$1',
                                    strtolower(
                                        htmlentities(
                                            $string,
                                            ENT_COMPAT,
                                            'utf-8'
                                        )
                                    )
                                )
                            ),
                            ENT_COMPAT,
                            'utf-8'
                        )
                    )
                )
            ),
            '-'
        );
    }
}
