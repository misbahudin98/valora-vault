<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencyProfile extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'agency_profiles';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'agency_name',
        'legal_name',
        'tagline',
        'email',
        'hotline_wa',
        'operational_address',
        'github_url',
        'website_url',
        'logo_url',
        'tax_id',
        'bank_name',
        'bank_account_no',
        'bank_account_name',
        'secondary_bank_name',
        'secondary_bank_account_no',
        'secondary_bank_account_name',
        'signatory_name',
        'signatory_title',
        'terms_notes',
    ];

    /**
     * Singleton accessor for agency profile with resilient fallback defaults.
     */
    public static function getProfile(): self
    {
        $profile = self::first();

        if (! $profile) {
            $profile = self::create([
                'agency_name' => 'PT Valora Digital Solusindo',
                'legal_name' => 'PT Valora Digital Solusindo',
                'tagline' => 'Software Engineering & Enterprise Web Solutions',
                'email' => 'billing@valora.id',
                'hotline_wa' => '+62 812-9988-7766',
                'operational_address' => 'Gedung Cyber One, Lantai 5, Jl. H.R. Rasuna Said Kav. 8, Jakarta Selatan 12950',
                'github_url' => 'https://github.com/valora-digital',
                'website_url' => 'https://valora.id',
                'logo_url' => null,
                'tax_id' => '01.456.789.0-012.000',
                'bank_name' => 'Bank Central Asia (BCA)',
                'bank_account_no' => '827-091-8888',
                'bank_account_name' => 'PT Valora Digital Solusindo',
                'secondary_bank_name' => 'Bank Mandiri',
                'secondary_bank_account_no' => '132-00-998877-6',
                'secondary_bank_account_name' => 'PT Valora Digital Solusindo',
                'signatory_name' => 'Muhammad Iqbal Murtadho',
                'signatory_title' => 'Managing Director & Lead Architect',
                'terms_notes' => "1. Pembayaran dinyatakan sah setelah dana efektif masuk ke rekening koran resmi a.n. PT Valora Digital Solusindo.\n2. Masa garansi pemeliharaan teknis & perbaikan bug aktif selama 30 hari kalender sejak tanggal serah terima aplikasi.\n3. Hak cipta dan seluruh berkas kode sumber (source code) diserahkan penuh kepada klien setelah penyelesaian pelunasan 100%.",
            ]);
        }

        return $profile;
    }
}
