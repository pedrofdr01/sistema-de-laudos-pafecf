<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\PDV;
use phpDocumentor\Reflection\Types\Void_;

/**
 * Classe Model do Laudo
 * @author Leonardo Lima e Pedro Rocha
 * @version 1.0
 * @copyright NPI © 2022, Núcleo de Práticas em Informática LTDA.
 * @access public
 */
class Laudo extends Model
{
    /**
     * Valores das varíaveis/campos do banco de dados sempre tem que estar declarados como fillable.
     */
    protected $table = "laudos";
    protected $fillable = [
        'razao_social_empresa',
        'nome_comercial_pdv', 'homologador', 'data_inicio',
        'data_termino', 'envelope_seguranca_marca', 'envelope_seguranca_modelo',
        'numero_envelope', 'requisitos_executados_sgbd', 'executavel_sgbd',
        'funcao_sped', 'executavel_sped', 'executavel_nfe', 'parecer_conclusivo',
        'ecf_analise_marca', 'ecf_analise_modelo', 'relacao_ecfs_marca', 'relacao_ecfs_modelo', 'comentarios'
    ];

    /**
     * Função responsável por receber o array de entradas da checkbox
     *  e transformar num longText antes de passar para o controller.
     * @param $value - objeto contendo os arrays a serem trasnformados antes de passagem para o controller.
     * @return void
     */
    public function setCategoryAttribute($value)
    {
        $this->attributes['relacao_ecfs'] = json_encode($value);
    }

    /**
     * Função responsável por fazer o relacionamento entre os PDVs e o Laudo.
     * @return void
     */
    public function pdvs()
    {
        return $this->hasMany(PDV::class, "id_pdv", "id");
    }

    /**
     * Função responsável por receber as marcas e modelos e passar para o controller.
     * @return void
     */

    public function ecfsMarcas()
    {
        $teste =  $this->hasMany(Modelo::class, "marca_id", "id");
    }
}
