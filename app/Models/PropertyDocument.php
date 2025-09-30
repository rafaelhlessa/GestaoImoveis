<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToProprietario;

class PropertyDocument extends Model
{
    protected $table = 'property_documents';

    protected $fillable = [
        'name', 'date', 'show', 'file', 'file_name', 'property_id'
    ];

    use BelongsToProprietario;

    protected static function booted()
    {
        // static::bootBelongsToProprietario();
    }

    /**
     * Obtém a URL pública do arquivo
     */
    public function getFileUrlAttribute()
    {
        return route('property.document.serve', $this->id);
    }

    /**
     * Obtém o conteúdo do arquivo decodificado
     */
    public function getFileContent()
    {
        if (!$this->file) {
            return null;
        }

        return base64_decode($this->file);
    }

    /**
     * Verifica se é um arquivo KML/KMZ
     */
    public function isKmlFile()
    {
        $extension = strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
        return in_array($extension, ['kml', 'kmz']);
    }

    /**
     * Obtém o MIME type baseado na extensão do arquivo
     */
    public function getMimeType()
    {
        $extension = strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));

        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'kml' => 'application/vnd.google-earth.kml+xml',
            'kmz' => 'application/vnd.google-earth.kmz',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Obtém o tamanho do arquivo em bytes
     */
    public function getFileSizeAttribute()
    {
        if (!$this->file) {
            return 0;
        }

        // Calcular tamanho baseado no Base64
        $base64Length = strlen($this->file);
        return intval($base64Length * 0.75); // Base64 adiciona ~33% de overhead
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
}
