            @if ($activeTab === 'edpm_pesantren')
                <x-akreditasi.edpm-review
                    :komponens="$komponens"
                    :evaluasis="$pesantrenEvaluasis"
                    :links="$pesantrenLinks"
                    :catatans="$pesantrenCatatans"
                    title="EDPM/IPR Pesantren"
                    subtitle="Detail komponen EDPM dan IPR, isian evaluasi diri, tautan bukti, dan catatan pesantren."
                />
            @endif
