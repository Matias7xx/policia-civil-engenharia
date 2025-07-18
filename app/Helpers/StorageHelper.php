<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class StorageHelper
{
    /**
     * Obter instância do Storage com bucket específico
     */
    public static function bucket($bucketName)
    {
        // cópia da configuração do s3
        $config = config('filesystems.disks.s3');
        
        // Alterar apenas o bucket
        $config['bucket'] = $bucketName;
        
        // Criar configuração temporária
        Config::set('filesystems.disks.s3_temp', $config);
        
        // Retorna instância do Storage com o bucket específico
        return Storage::disk('s3_temp');
    }
    
    /**
     * Bucket de engenharia (unidades)
     */
    public static function engenharia()
    {
        return self::bucket(env('AWS_BUCKET', 'engenharia'));
    }
    
    /**
     * Gerar caminho para fotos da unidade
     */
    public static function getFotosPath($unidade)
    {
        $unidadeSlug = Str::slug($unidade->nome, '-');
        return "unidade-{$unidade->id}-{$unidadeSlug}/fotos";
    }
    
    /**
     * Gerar caminho para documentos da unidade
     */
    public static function getDocumentosPath($unidade)
    {
        $unidadeSlug = Str::slug($unidade->nome, '-');
        return "unidade-{$unidade->id}-{$unidadeSlug}/documentos";
    }
    
    /**
     * Salvar foto da unidade no MinIO
     */
    public static function salvarFoto($unidade, $file, $nomeArquivo = null)
    {
        $path = self::getFotosPath($unidade);
        
        if (!$nomeArquivo) {
            $nomeArquivo = $file->getClientOriginalName();
        }
        
        $caminhoCompleto = "{$path}/{$nomeArquivo}";
        
        self::engenharia()->putFileAs($path, $file, $nomeArquivo);
        
        return $caminhoCompleto;
    }
    
    /**
     * Salvar documento da unidade no MinIO
     */
    public static function salvarDocumento($unidade, $file, $nomeArquivo = null)
    {
        $path = self::getDocumentosPath($unidade);
        
        if (!$nomeArquivo) {
            $nomeArquivo = $file->getClientOriginalName();
        }
        
        $caminhoCompleto = "{$path}/{$nomeArquivo}";
        
        self::engenharia()->putFileAs($path, $file, $nomeArquivo);
        
        return $caminhoCompleto;
    }
    
    /**
     * Remover arquivo do MinIO
     */
    public static function removerArquivo($caminho)
    {
        if (self::engenharia()->exists($caminho)) {
            return self::engenharia()->delete($caminho);
        }
        return false;
    }
    
    /**
     * Verificar se arquivo existe no MinIO
     */
    public static function arquivoExiste($caminho)
    {
        return self::engenharia()->exists($caminho);
    }
    
    /**
     * Obter conteúdo do arquivo do MinIO
     */
    public static function obterArquivo($caminho)
    {
        if (self::engenharia()->exists($caminho)) {
            return self::engenharia()->get($caminho);
        }
        return null;
    }
    
    /**
     * Gerar URL pública para visualização de arquivo
     */
    public static function obterUrlPublica($caminho)
    {
        return self::engenharia()->url($caminho);
    }
}