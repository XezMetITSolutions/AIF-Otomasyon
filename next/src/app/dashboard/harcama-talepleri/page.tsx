"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Wallet, Send, CheckCircle2, XCircle, AlertCircle, Trash2, Tag, CalendarClock } from "lucide-react";
import { getHarcamaTalepleriAction, actionHarcamaTalebi } from "../../actions/auth";

const kategoriListesi: Record<string, string> = {
  otel: "Otel Rezervasyonu",
  ucak: "Uçak Bileti",
  seminer: "Seminer Odası",
  araba: "Kiralık Araba",
  seyahat: "Seyahat / Diğer",
  ikram: "İkram / Davet",
  genel: "Diğer"
};

export default function HarcamaTalepleriPage() {
  const [activeTab, setActiveTab] = useState<"talebim" | "onay">("talebim");
  const [requests, setRequests] = useState<any[]>([]);
  const [hasPermissionBaskan, setHasPermissionBaskan] = useState(false);
  const [hasPermissionUye, setHasPermissionUye] = useState(false);
  const [loading, setLoading] = useState(true);
  
  // Filters
  const [durumFilter, setDurumFilter] = useState("");

  // Form Stats
  const [baslik, setBaslik] = useState("");
  const [kategori, setKategori] = useState("genel");
  const [tutar, setTutar] = useState("");
  const [aciklama, setAciklama] = useState("");
  const [submitLoading, setSubmitLoading] = useState(false);
  const [message, setMessage] = useState<{ text: string; type: "success" | "error" } | null>(null);

  // Reject Modal State
  const [rejectModalOpen, setRejectModalOpen] = useState(false);
  const [rejectId, setRejectId] = useState<number | null>(null);
  const [rejectReason, setRejectReason] = useState("");

  useEffect(() => {
    loadRequests();
  }, [activeTab, durumFilter]);

  async function loadRequests() {
    setLoading(true);
    const res = await getHarcamaTalepleriAction({ tab: activeTab, durum: durumFilter });
    if (res.success) {
      setRequests(res.requests || []);
      setHasPermissionBaskan(res.hasPermissionBaskan);
      setHasPermissionUye(res.hasPermissionUye);
      
      if (!res.hasPermissionUye && activeTab === 'talebim' && res.hasPermissionBaskan) {
        setActiveTab('onay');
      }
    } else {
      setMessage({ text: res.error || "Talepler yüklenemedi.", type: "error" });
    }
    setLoading(false);
  }

  const showMessage = (text: string, type: "success" | "error") => {
    setMessage({ text, type });
    setTimeout(() => setMessage(null), 4000);
  };

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!baslik || !tutar) return;
    setSubmitLoading(true);

    const res = await actionHarcamaTalebi({ action: "yeni_harcama", baslik, tutar, kategori, aciklama });
    
    if (res.success) {
      showMessage(res.message, "success");
      setBaslik(""); setTutar(""); setKategori("genel"); setAciklama("");
      await loadRequests();
    } else {
      showMessage(res.error, "error");
    }
    setSubmitLoading(false);
  };

  const handleAction = async (actionStr: string, id: number, extraData: any = {}) => {
    if (actionStr === 'delete' && !confirm("Bu harcama talebini silmek istediğinize emin misiniz?")) return;
    if (actionStr === 'approve' && !confirm("Talebi onaylamak istediğinize emin misiniz?")) return;

    const res = await actionHarcamaTalebi({ action: actionStr, talep_id: id, ...extraData });
    if (res.success) {
      showMessage(res.message, "success");
      if (actionStr === 'reject') setRejectModalOpen(false);
      await loadRequests();
    } else {
      showMessage(res.error, "error");
    }
  };

  const openRejectModal = (id: number) => {
    setRejectId(id);
    setRejectReason("");
    setRejectModalOpen(true);
  };

  const submitReject = () => {
    if (!rejectId || !rejectReason.trim()) return;
    handleAction('reject', rejectId, { aciklama: rejectReason });
  };

  const getStatusBadge = (durum: string) => {
    switch (durum) {
      case 'onaylandi': return <span className="text-[10px] px-2 py-0.5 rounded-md font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20">TAM ONAYLANDI</span>;
      case 'ilk_onay': return <span className="text-[10px] px-2 py-0.5 rounded-md font-bold text-sky-400 bg-sky-500/10 border border-sky-500/20">1. SEVİYE ONAYLANDI</span>;
      case 'reddedildi': return <span className="text-[10px] px-2 py-0.5 rounded-md font-bold text-red-400 bg-red-500/10 border border-red-500/20">REDDEDİLDİ</span>;
      case 'odenmistir': return <span className="text-[10px] px-2 py-0.5 rounded-md font-bold text-purple-400 bg-purple-500/10 border border-purple-500/20">ÖDENDİ</span>;
      default: return <span className="text-[10px] px-2 py-0.5 rounded-md font-bold text-amber-400 bg-amber-500/10 border border-amber-500/20">BEKLEMEDE</span>;
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString("tr-TR");
  };

  if (loading && requests.length === 0) {
    return <div className="flex items-center justify-center py-20 text-emerald-500 font-bold">Veriler Yükleniyor...</div>;
  }

  return (
    <div className="space-y-6">
      {/* HEADER SECTION */}
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
            <CalendarClock className="w-6 h-6 text-emerald-400" /> Rezervasyon Talepleri
          </h1>
          <p className="text-zinc-500 text-sm mt-1">Seyahat, konaklama ve organizasyon rezervasyonlarını yönetin.</p>
        </div>

        {/* TABS */}
        <div className="flex bg-zinc-950 rounded-xl p-1 border border-white/5 w-full md:w-auto">
          {hasPermissionUye && (
            <button 
              onClick={() => setActiveTab("talebim")}
              className={`flex-1 md:flex-none px-6 py-2 rounded-lg text-sm font-medium transition-all ${
                activeTab === "talebim" ? "bg-emerald-500/10 text-emerald-400 border border-emerald-500/20" : "text-zinc-400 hover:text-white"
              }`}
            >
              Rezervasyon Taleplerim
            </button>
          )}
          {hasPermissionBaskan && (
            <button 
              onClick={() => setActiveTab("onay")}
              className={`flex-1 md:flex-none px-6 py-2 rounded-lg text-sm font-medium transition-all ${
                activeTab === "onay" ? "bg-emerald-500/10 text-emerald-400 border border-emerald-500/20" : "text-zinc-400 hover:text-white"
              }`}
            >
              Rezervasyon Onayları
            </button>
          )}
        </div>
      </div>

      <AnimatePresence>
        {message && (
          <motion.div
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -10 }}
            className={`p-4 rounded-xl flex items-center gap-3 border ${
              message.type === "success" ? "bg-emerald-500/10 border-emerald-500/20 text-emerald-400" : "bg-red-500/10 border-red-500/20 text-red-400"
            }`}
          >
            {message.type === "success" ? <CheckCircle2 className="w-5 h-5" /> : <XCircle className="w-5 h-5" />}
            <span className="text-sm font-medium">{message.text}</span>
          </motion.div>
        )}
      </AnimatePresence>

      {/* REJECT MODAL */}
      <AnimatePresence>
        {rejectModalOpen && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <motion.div 
              initial={{ scale: 0.95, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.95, opacity: 0 }}
              className="bg-zinc-900 border border-white/10 rounded-2xl p-6 w-full max-w-md shadow-2xl"
            >
              <h3 className="text-lg font-bold text-white mb-2 flex items-center gap-2"><XCircle className="w-5 h-5 text-red-500" /> Talebi Reddet</h3>
              <p className="text-sm text-zinc-400 mb-4">Bu harcama talebini reddetme nedeninizi aşağıda belirtin.</p>
              
              <textarea
                value={rejectReason}
                onChange={(e) => setRejectReason(e.target.value)}
                placeholder="Örn: Bu harcama için fatura eksik yüklenmiş..."
                className="w-full bg-zinc-950 border border-white/10 rounded-xl p-3 text-sm text-white focus:border-red-500/50 outline-none min-h-[100px] mb-4"
              />
              
              <div className="flex justify-end gap-2">
                <button onClick={() => setRejectModalOpen(false)} className="px-4 py-2 rounded-xl text-sm font-medium bg-zinc-800 text-zinc-300 hover:bg-zinc-700">İptal</button>
                <button onClick={submitReject} className="px-4 py-2 rounded-xl text-sm font-medium bg-red-600 text-white hover:bg-red-500">Reddet</button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {/* TALEPLERİM EKRANI */}
        {activeTab === "talebim" && (
          <>
            <div className="lg:col-span-1">
              <div className="bg-zinc-900 border border-white/5 rounded-2xl overflow-hidden sticky top-24">
                <div className="p-5 border-b border-white/5 bg-zinc-900/50">
                  <h2 className="font-semibold text-white">Yeni Rezervasyon Talebi</h2>
                </div>
                <form onSubmit={handleCreate} className="p-5 space-y-4">
                  <div className="space-y-1.5 flex flex-col">
                    <label className="text-xs font-semibold text-zinc-400">Başlık (Örn. Uçak Bileti)</label>
                    <input type="text" value={baslik} onChange={(e) => setBaslik(e.target.value)} required className="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-200 focus:border-emerald-500/50 focus:ring-1 outline-none" />
                  </div>
                  <div className="space-y-1.5 flex flex-col">
                    <label className="text-xs font-semibold text-zinc-400">Kategori</label>
                    <select value={kategori} onChange={(e) => setKategori(e.target.value)} required className="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-200 focus:border-emerald-500/50 focus:ring-1 outline-none">
                      {Object.keys(kategoriListesi).map(key => (
                        <option key={key} value={key}>{kategoriListesi[key]}</option>
                      ))}
                    </select>
                  </div>
                  <div className="space-y-1.5 flex flex-col">
                    <label className="text-xs font-semibold text-zinc-400">Tutar (€)</label>
                    <input type="number" step="0.01" min="0.01" value={tutar} onChange={(e) => setTutar(e.target.value)} required className="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-200 focus:border-emerald-500/50 focus:ring-1 outline-none font-bold text-emerald-400" placeholder="0.00" />
                  </div>
                  <div className="space-y-1.5 flex flex-col">
                    <label className="text-xs font-semibold text-zinc-400">Açıklama</label>
                    <textarea rows={3} value={aciklama} onChange={(e) => setAciklama(e.target.value)} className="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-200 focus:border-emerald-500/50 focus:ring-1 outline-none resize-none" placeholder="Masraf detaylarını belirtin..."></textarea>
                  </div>
                  <button type="submit" disabled={submitLoading || !baslik || !tutar} className="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-medium py-3 rounded-xl transition-all flex items-center justify-center gap-2">
                    <Send className="w-4 h-4" /> Talep Gönder
                  </button>
                </form>
              </div>
            </div>

            <div className="lg:col-span-2 space-y-4">
              <div className="bg-zinc-900 border border-white/5 rounded-2xl overflow-hidden min-h-[400px]">
                <div className="p-5 border-b border-white/5 bg-zinc-900/50 flex justify-between items-center">
                  <h2 className="font-semibold text-white">Geçmiş Rezervasyonlarım</h2>
                  <span className="text-xs font-bold bg-zinc-800 text-zinc-400 px-2 py-1 rounded-md">{requests.length} Kayıt</span>
                </div>
                
                <div className="p-4 space-y-3">
                  {requests.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-10 opacity-50">
                      <Wallet className="w-10 h-10 text-zinc-500 mb-2" />
                      <p className="text-zinc-400 text-sm">Henüz bir rezervasyon talebiniz bulunmuyor.</p>
                    </div>
                  ) : (
                    requests.map(req => {
                      const catName = kategoriListesi[req.meta?.kategori || "genel"] || "Genel";
                      return (
                        <div key={req.talep_id} className="bg-zinc-950 border border-white/5 rounded-xl p-4 flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
                          <div className="flex-1">
                            <div className="flex flex-wrap items-center gap-3 mb-1">
                              {getStatusBadge(req.durum)}
                              <span className="text-sm font-bold text-white">{req.baslik}</span>
                              <span className="flex items-center gap-1 text-[10px] text-zinc-400 bg-zinc-900 px-2 py-0.5 rounded-md border border-white/5"><Tag className="w-3 h-3" /> {catName}</span>
                            </div>
                            {req.kisa_aciklama && <p className="text-xs text-zinc-500 mt-2 line-clamp-1">{req.kisa_aciklama}</p>}
                            {(req.ilk_onay_aciklama || req.ikinci_onay_aciklama) && (req.durum === 'reddedildi' || req.durum === 'onaylandi') && (
                              <p className="text-xs mt-2 p-2 rounded-lg bg-white/5 border border-white/5 flex gap-1">
                                <span className="text-zinc-400 font-bold">Yönetici Notu:</span> <span className="text-zinc-300">{req.ikinci_onay_aciklama || req.ilk_onay_aciklama}</span>
                              </p>
                            )}
                          </div>
                          <div className="flex flex-col items-end gap-1 min-w-[100px]">
                            <span className="text-lg font-bold text-emerald-400">{Number(req.tutar).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} €</span>
                            <span className="flex items-center gap-1 text-[10px] text-zinc-500"><CalendarClock className="w-3 h-3" /> {formatDate(req.olusturma_tarihi)}</span>
                          </div>
                        </div>
                      );
                    })
                  )}
                </div>
              </div>
            </div>
          </>
        )}

        {/* YÖNETİM (ONAY) EKRANI */}
        {activeTab === "onay" && (
          <div className="lg:col-span-3 space-y-4">
            {/* ONAY FILTER */}
            <div className="flex gap-2">
              {[
                { label: "Tümü", val: "" },
                { label: "Bekleyenler", val: "beklemede" },
                { label: "1. Onay", val: "ilk_onay" },
                { label: "Tam Onay", val: "onaylandi" },
                { label: "Reddedilen", val: "reddedildi" }
              ].map(f => (
                <button 
                  key={f.val}
                  onClick={() => setDurumFilter(f.val)}
                  className={`px-4 py-2 rounded-xl text-xs font-bold transition-all ${
                    durumFilter === f.val 
                      ? "bg-zinc-800 text-white border border-white/10" 
                      : "bg-zinc-900 border border-white/5 text-zinc-400 hover:text-zinc-200"
                  }`}
                >
                  {f.label}
                </button>
              ))}
            </div>

            <div className="grid grid-cols-1 xl:grid-cols-2 gap-4">
              {requests.length === 0 ? (
                <div className="col-span-full py-10 flex justify-center text-zinc-500">Bu filtrede kayıt bulunamadı veya onay sınırınızı aşıyor.</div>
              ) : (
                requests.map(req => {
                  const catName = kategoriListesi[req.meta?.kategori || "genel"] || "Genel";
                  const level = Number(req.onay_seviyesi || 0);

                  return (
                    <div key={req.talep_id} className="bg-zinc-900 border border-white/5 rounded-2xl p-5 hover:border-emerald-500/20 transition-all flex flex-col justify-between">
                      <div>
                        <div className="flex justify-between items-start mb-3">
                          {getStatusBadge(req.durum)}
                          <span className="text-xl font-bold tracking-tight text-emerald-400">{Number(req.tutar).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} €</span>
                        </div>
                        
                        <div className="flex items-center gap-3 mb-4 border-b border-white/5 pb-4">
                             <div className="w-10 h-10 rounded-full bg-zinc-800 flex items-center justify-center font-bold text-zinc-400 shadow-inner text-sm uppercase">
                               {req.kullanici_adi?.split(' ')?.map((n: string) => n[0])?.join('') || "-"}
                             </div>
                          <div className="flex-1">
                            <h3 className="font-bold text-white text-sm leading-tight">{req.kullanici_adi}</h3>
                            <span className="text-[10px] text-zinc-500">{req.email}</span>
                          </div>
                          <div className="text-right">
                             <div className="text-[10px] text-zinc-500 tracking-wider">REZERVASYON ID</div>
                             <div className="text-xs font-mono font-bold text-zinc-400">#{req.talep_id}</div>
                          </div>
                        </div>

                        <div className="space-y-3 mb-4">
                          <div className="flex items-start justify-between">
                            <div>
                              <div className="text-sm font-bold text-zinc-200">{req.baslik}</div>
                              <div className="text-xs text-zinc-500 mt-1 flex items-center gap-1"><Tag className="w-3 h-3" /> {catName}</div>
                            </div>
                          </div>
                          {req.kisa_aciklama && (
                             <div className="bg-zinc-950 p-2.5 rounded-xl border border-white/5 text-xs text-zinc-400">
                               <span className="text-zinc-500 font-semibold block mb-1">Kullanıcı Açıklaması:</span>
                               {req.kisa_aciklama}
                             </div>
                          )}

                          {/* ONAY AKIŞI GÖRÜNTÜLEYİCiSİ */}
                          <div className="flex flex-col gap-1 mt-3">
                            {level >= 1 && req.ilk_onaylayan_ad && (
                               <div className="flex items-center gap-2 text-[10px] text-sky-400">
                                  <CheckCircle2 className="w-3.5 h-3.5" /> 1. Seviye: {req.ilk_onaylayan_ad} onayladı.
                               </div>
                            )}
                            {level >= 2 && req.ikinci_onaylayan_ad && (
                               <div className="flex items-center gap-2 text-[10px] text-emerald-400">
                                  <CheckCircle2 className="w-3.5 h-3.5" /> 2. Seviye: {req.ikinci_onaylayan_ad} onayladı.
                               </div>
                            )}
                          </div>
                        </div>
                      </div>

                      <div className="flex gap-2 border-t border-white/5 pt-4">
                        {(req.canApprove1 || req.canApprove2) ? (
                          <>
                            <button onClick={() => handleAction('approve', req.talep_id)} className="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg py-2 mt-auto text-xs font-bold transition-all shadow-md flex items-center justify-center gap-1">
                              <CheckCircle2 className="w-4 h-4" /> BİR SONRAKİ AŞAMAYA ONAYLA
                            </button>
                            <button onClick={() => openRejectModal(req.talep_id)} className="bg-zinc-800 hover:bg-red-600 hover:text-white px-4 text-red-500 rounded-lg py-2 mt-auto text-xs font-bold transition-all flex items-center justify-center gap-1">
                              <XCircle className="w-4 h-4" /> RED
                            </button>
                          </>
                        ) : req.durum === 'beklemede' || req.durum === 'ilk_onay' ? (
                          <div className="w-full text-center text-xs text-amber-500 bg-amber-500/10 py-2 border border-amber-500/20 rounded-lg font-bold">
                             DİĞER YÖNETİCİ ONAYI BEKLENİYOR
                          </div>
                        ) : (
                          <button onClick={() => handleAction('delete', req.talep_id)} className="w-full bg-zinc-950 hover:bg-red-500/20 hover:text-red-400 text-zinc-600 border border-white/5 rounded-lg py-2 text-xs font-bold transition-all flex items-center justify-center gap-1">
                            <Trash2 className="w-4 h-4" /> TALEBİ SİL
                          </button>
                        )}
                      </div>
                    </div>
                  );
                })
              )}
            </div>
          </div>
        )}

      </div>
    </div>
  );
}
