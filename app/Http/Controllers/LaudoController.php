<?php

namespace App\Http\Controllers;

use App\Models\Laudo;
use App\Models\Empresa;
use App\Models\PDV;
use App\Models\Ecfs;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreLaudoRequest;
use App\Http\Requests\StoreLaudoUpdateRequest;
use PhpOffice\PhpWord\TemplateProcessor;

class LaudoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $buscar = request('buscar');
        if ($buscar) {
            $laudos = Laudo::where([
                ['razao_social_empresa', 'LIKE', "%{$buscar}%"]
            ])->orderBy('id', 'desc')->paginate(10);
            return view('laudo.index', ['laudos' => $laudos, 'buscar' => $buscar]);
        }
        $laudos = Laudo::where('ifl', 'LIKE', "%IFL%")->orderBy('id', 'desc')->paginate(10);
        return view('laudo.index', ['laudos' => $laudos, 'buscar' => $buscar]);
    }

    public function create()
    {
        $ecfs = DB::table('ecfs')
            ->select('marca')->distinct()->get();
        $relacao_ecfs = Ecfs::all();
        $empresas = Empresa::where('validacao', true)->orderBy('id', 'desc')->get();
        $pdvs = PDV::where('validacao', true)->orderBy('id', 'desc')->get();
        return view('laudo.create', ['empresas' => $empresas, 'pdvs' => $pdvs, 'ecfs' => $ecfs, 'relacao_ecfs' => $relacao_ecfs]);
    }

    public function gerarIFL()
    {
        $numero_laudo = 1;
        $ano_atual = Carbon::now()->year;
        $ultimo_laudo =  Laudo::latest('id')->first();
        if ($ultimo_laudo == null) {
            return $numero_laudo;
        }
        $ano_ultimo_laudo = $ultimo_laudo->select('created_at')->first();
        $ano_ultimo_laudo = \Carbon\Carbon::parse($ano_ultimo_laudo->created_at)->year;



        if ($ano_atual == $ano_ultimo_laudo) {
            $numero_laudo = $ultimo_laudo->numero_laudo + 1;
            return $numero_laudo;
        } else {
            return $numero_laudo;
        }
    }

    public function store(StoreLaudoRequest $request)
    {

        $laudo = new Laudo;
        $pdv = PDV::find($request->pdv);
        $user = auth()->user();
        $ano_atual = Carbon::now()->year;

        $laudo->numero_laudo = $this->gerarIFL();

        if ($laudo->numero_laudo < 10) {
            $laudo->ifl .= "IFL00" . $laudo->numero_laudo . $ano_atual;
        } else {
            $laudo->ifl .= "IFL0" . $laudo->numero_laudo . $ano_atual;
        }

        $ecf = Ecfs::find($request->ecf_analise_modelo);

        $laudo->id_pdv = $pdv->id;
        $laudo->id_empresa = $pdv->empresa->id;
        $laudo->razao_social_empresa = $pdv->empresa->razao_social;
        $laudo->nome_comercial_pdv = $pdv->nome_comercial;
        $laudo->homologador = $user->name;
        $laudo->data_inicio = $request->data_inicio;
        $laudo->data_termino = $request->data_termino;
        $laudo->versao_er = $request->versao_er;
        $laudo->envelope_seguranca_marca = $request->envelope_seguranca_marca;
        $laudo->envelope_seguranca_modelo = $request->envelope_seguranca_modelo;
        $laudo->numero_envelope = $request->numero_envelope;
        $laudo->requisitos_executados_sgbd = $request->requisitos_executados_sgbd;
        $laudo->executavel_sgbd = $request->executavel_sgbd;
        $laudo->funcao_sped = $request->funcao_sped;
        $laudo->executavel_sped = $request->executavel_sped;
        $laudo->executavel_nfe = $request->executavel_nfe;
        $laudo->parecer_conclusivo = $request->parecer_conclusivo;
        $laudo->ecf_analise_marca = $request->ecf_analise_marca;
        $laudo->ecf_analise_modelo = $ecf->modelo;
        //$laudo->relacao_ecfs = implode(", ", $request->relacao_ecfs);
        $laudo->relacao_ecfs = implode(", ", $request->input('relacao_ecfs'));
        $laudo->comentarios = $request->comentarios;
        $laudo->responsavel_testes = $request->responsavel_testes;

        $laudo->save();

        return redirect('/laudo')->with('msg', 'Laudo Cadastrado com Sucesso!!');
    }

    public function getPDVs()
    {
        $id_empresa = request('empresa');
        $pdvs = PDV::where([
            ['id_empresa', $id_empresa],
            ['validacao', true]
        ])->get();
        $option = "<option value=''>Selecione um PDV</option>";
        foreach ($pdvs as $pdv) {
            $option .= '<option value="' . $pdv->id . '">' . $pdv->nome_comercial . '</option>';
        }
        return $option;
    }

    public function getModelos()
    {
        $marca = request('ecf_analise_marca');
        $ecfs = Ecfs::where([
            ['marca', 'LIKE', $marca]
        ])->get();

        $option = "<option value=''>Selecione um Modelo</option>";
        foreach ($ecfs as $ecf) {
            $option .= '<option value="' . $ecf->id . '">' . $ecf->modelo . '</option>';
        }
        return $option;
    }

    public function show($id)
    {
        $ecfs = DB::table('ecfs')
            ->select('marca')->distinct()->get();
        $laudo = Laudo::find($id);
        $relacao_ecfs = Ecfs::all();
        $ecfs_selecionadas = explode(", ", $laudo->relacao_ecfs);
        return view('laudo.show', ['laudo' => $laudo, 'ecfs' => $ecfs, 'relacao_ecfs' => $relacao_ecfs, 'ecfs_selecionadas' => $ecfs_selecionadas]);
    }

    public function update(StoreLaudoUpdateRequest $request, $id)
    {
        $laudo = Laudo::find($id);
        $ecf = Ecfs::find($request->input('ecf_analise_modelo'));
        $ecf_modelo = $ecf->modelo;
        if (
            $laudo->data_inicio == $request->input('data_inicio') &&
            $laudo->data_termino == $request->input('data_termino') &&
            $laudo->versao_er == $request->input('versao_er') &&
            $laudo->envelope_seguranca_marca == $request->input('envelope_seguranca_marca') &&
            $laudo->envelope_seguranca_modelo == $request->input('envelope_seguranca_modelo') &&
            $laudo->numero_envelope == $request->input('numero_envelope') &&
            $laudo->requisitos_executados_sgbd == $request->input('requisitos_executados_sgbd') &&
            $laudo->executavel_sgbd == $request->input('executavel_sgbd') &&
            $laudo->funcao_sped == $request->input('funcao_sped') &&
            $laudo->executavel_sped == $request->input('executavel_sped') &&
            $laudo->executavel_nfe == $request->input('executavel_nfe') &&
            $laudo->parecer_conclusivo == $request->input('parecer_conclusivo') &&
            $laudo->ecf_analise_modelo == $ecf->ecf_analise_modelo &&
            $laudo->relacao_ecfs == $request->input('relacao_ecfs') &&
            $laudo->comentarios == $request->input('comentarios') &&
            $laudo->responsavel_testes == $request->input('responsavel_testes')
        ) {
            return redirect()->back()->with('msgerro', 'Nenhum campo alterado!!');
        } else {
            $laudo->data_inicio = $request->input('data_inicio');
            $laudo->data_termino = $request->input('data_termino');
            $laudo->versao_er = $request->input('versao_er');
            $laudo->envelope_seguranca_marca = $request->input('envelope_seguranca_marca');
            $laudo->envelope_seguranca_modelo = $request->input('envelope_seguranca_modelo');
            $laudo->numero_envelope = $request->input('numero_envelope');
            $laudo->requisitos_executados_sgbd = $request->input('requisitos_executados_sgbd');
            $laudo->executavel_sgbd = $request->input('executavel_sgbd');
            $laudo->funcao_sped = $request->input('funcao_sped');
            $laudo->executavel_sped = $request->input('executavel_sped');
            $laudo->executavel_nfe = $request->input('executavel_nfe');
            $laudo->parecer_conclusivo = $request->input('parecer_conclusivo');
            $laudo->ecf_analise_modelo = $ecf_modelo;
            $laudo->relacao_ecfs = $request->input('relacao_ecfs');
            $laudo->comentarios = $request->input('comentarios');
            $laudo->responsavel_testes = $request->input('responsavel_testes');

            $laudo->save();
            return redirect()->back()->with('msg', 'Laudo Editado com Sucesso!!');
        }
    }


    public function destroy($id)
    {
        $laudo = Laudo::find($id);
        $laudo->delete();
        return redirect()->route('laudo.index')->with('msgerro', 'Laudo Excluído com Sucesso!!');
    }

    public function carregarArquivos()
    {
        $arquivo_tmp = $_FILES['md5']['tmp_name'];
        $dados = file($arquivo_tmp);

        foreach ($dados as $linha) {
            $linha = trim($linha);
            $valor = explode(' ', $linha);
            var_dump($valor);
        }
    }

    public function viewGerarDocs($id_laudo)
    {
        $laudo = Laudo::find($id_laudo);
        $pdv = PDV::find($laudo->id_pdv);
        $empresa = Empresa::find($pdv->empresa->id);
        return view('laudo.gerarDocs', ['laudo' => $laudo, 'pdv' => $pdv, 'empresa' => $empresa]);
    }

    public function gerarLaudo($id_laudo)
    {
        $laudo = Laudo::find($id_laudo);
        $pdv = PDV::find($laudo->id_pdv);
        $empresa = Empresa::find($pdv->empresa->id);
        $templateProcessor = new TemplateProcessor('ModeloLaudoPAFECF.docx');
        $templateProcessor->setValues(array(
            //informações da Empresa
            'txtCnpj' => $empresa->cnpj,
            'txtRazaoSocial' => $empresa->razao_social,
            'txtNomeFantasia' => $empresa->nome_fantasia,
            'txtEndereco' => $empresa->endereco,
            'txtBairro' => $empresa->bairro,
            'txtCidade' => $empresa->cidade,
            'txtUf' => $empresa->uf,
            'txtCep' => $empresa->cep,
            'txtTelefone' => $empresa->telefone,
            'txtCelular' => $empresa->celular,
            'txtIe' => $empresa->inscricao_estadual,
            'txtIm' => $empresa->inscricao_municipal,
            'txtRepresentante' => $empresa->representante,
            'txtCpfContato' => $empresa->cpf_representante,
            'txtRGRepresentante' => $empresa->rg_representante,
            'txtEmail' => $empresa->email_representante,
            //informações do Laudo
            'txtResponsavelTestes' => $laudo->responsavel_testes,
            'laudo' => $laudo->ifl,
            'txtNomeHomologador' => $laudo->homologador,
            'txtDataInicio' => $laudo->data_inicio,
            'txtDataFinal' => $laudo->data_termino,
            'txtEnvelope' => $laudo->numero_envelope,
            'txtEcfMarca' => $laudo->ecf_analise_marca,
            'txtEcfModelo' => $laudo->ecf_analise_modelo,
            'txtObservacaoOTC' => $laudo->comentarios,
            //'txtRelacaoEcf' => $laudo->relacao_ecfs,
            //pretendo pegar o sysdate
            'txtDataVersao' => $laudo->data_termino,
            //informações do PDV
            'txtNomeComercial' => $pdv->nome_comercial,
            'txtVersao' => $pdv->versao,
            'txtLinguagemProgramacao' => $pdv->linguagem,
            'txtSo' => $pdv->sistema_operacional,
            'txtBd' => $pdv->data_base,
        ));
        $pathToSave = 'salvo.docx';
        $templateProcessor->saveAs($pathToSave);
        return $this->index();
    }
}
