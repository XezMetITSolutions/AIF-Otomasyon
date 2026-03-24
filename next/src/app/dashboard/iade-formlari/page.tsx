"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { FileText, Plus, Trash2, Save } from "lucide-react";
import { getIadeFormlariAction } from "../../actions/auth";

const teskilatListesi = ["AT", "KT", "GT", "KGT"];
const birimListesi = [
  { id: "baskan", label: "Başkan" }, { id: "byk", label: "BYK Üyesi" }, { id: "egitim", label: "Eğitim" },
  { id: "idair", label: "İdari İşler" }, { id: "muhasebe", label: "Muhasebe" }, { id: "sosyal", label: "Sosyal Hizmetler" }
];
const turListesi = [
  { id: "genel", label: "Genel" }, { id: "ulasim_km", label: "Ulaşım - Kilometre" }, { id: "ulasim_fatura", label: "Ulaşım - Faturalı" },
  { id: "yemek", label: "Yemek/İkram" }, { id: "konaklama", label: "Konaklama" }, { id: "malzeme", label: "Malzeme" }
];

export default function IadeFormlariPage() {
  const [activeTab, setActiveTab] = useState<"talebim" | "onay">("talebim");
  const [requests, setRequests] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [message, setMessage] = useState<{ text: string; type: "success" | "error" } | null>(null);

  // Form State
  const [items, setItems] = useState<any[]>([{ teskilat: "AT", birim: "baskan", tur: "genel", odemeSekli: "faturasiz", tutar: "", aciklama: "" }]);
  const [iban, setIban] = useState("");
  const [submitLoading, setSubmitLoading] = useState(false);

  useEffect(() => { loadRequests(); }, [activeTab]);

  async function loadRequests() {
    setLoading(true);
    const res = await getIadeFormlariAction({ tab: activeTab });
    if (res.success) setRequests(res.requests || []);
    setLoading(false);
  }

  const addItemRow = () => {
    setItems([...items, { teskilat: "AT", birim: "baskan", tur: "genel", odemeSekli: "faturasiz", tutar: "", aciklama: "" }]);
  };

  const removeItemRow = (index: number) => {
    setItems(items.filter((_, i) => i !== index));
  };

  const updateItem = (index: number, field: string, value: string) => {
    const newItems = [...items];
    newItems[index] = { ...newItems[index], [field]: value };
    setItems(newItems);
  };

  const calculateTotal = () => {
    return items.reduce((sum, item) => sum + (parseFloat(item.tutar) || 0), 0);
  };

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitLoading(true);
    // Send request via ActionHarcamaTalebi equivalent if grouped items are supported inside API
    // (Simule: multiple API sends, or API update required)
    setTimeout(() => {
      setIsModalOpen(false);
      setMessage({ text: "İade talepleri başarıyla oluşturuldu (Simülasyon - API çoklu kalem güncellemesi gerekiyor).", type: "success" });
      setSubmitLoading(false);
    }, 1000);
  };

  return (
    <div className="p-6 space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-zinc-100 flex items-center gap-2">
            <FileText className="w-6 h-6 text-emerald-500" />
            İade Formları (Gider Bildirimi)
          </h1>
          <p className="text-sm text-zinc-400">Çoklu kalem harcamalarınızı ve iadelerinizi detaylı yönetin.</p>
        </div>
        <button onClick={() => setIsModalOpen(true)} className="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-sm font-semibold shadow-lg">
          <Plus className="w-4 h-4" />
          Yeni Gider Formu
        </button>
      </div>

      <div className="flex gap-2 border-b border-white/5 pb-2">
        <button onClick={() => setActiveTab("talebim")} className={`px-4 py-2 rounded-lg text-sm font-medium ${activeTab === "talebim" ? "bg-emerald-600 text-white" : "text-zinc-400"}`}>Taleplerim</button>
        <button onClick={() => setActiveTab("onay")} className={`px-4 py-2 rounded-lg text-sm font-medium ${activeTab === "onay" ? "bg-emerald-600 text-white" : "text-zinc-400"}`}>Onay Bekleyenler</button>
      </div>

      {loading ? ( <div className="text-center text-zinc-500 py-12">Yükleniyor...</div> ) : (
        <div className="bg-zinc-900/50 border border-white/5 rounded-2xl overflow-hidden shadow-xl">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b border-white/5 bg-zinc-900/80">
                <th className="p-4 text-xs font-semibold text-zinc-400">Tarih</th><th className="p-4 text-xs font-semibold text-zinc-400">Başlık</th><th className="p-4 text-xs font-semibold text-zinc-400">Tutar</th><th className="p-4 text-xs font-semibold text-zinc-400">Durum</th>
              </tr>
            </thead>
            <tbody>
              {requests.map((r, idx) => (
                <tr key={idx} className="border-b border-white/5 hover:bg-white/5 transition-colors">
                  <td className="p-4 text-sm text-zinc-300">{new Date(r.olusturma_tarihi || r.created_at).toLocaleDateString("tr-TR")}</td>
                  <td className="p-4 text-sm text-zinc-100 font-medium">{r.baslik || "Masraf Formu"}</td>
                  <td className="p-4 text-sm font-semibold text-emerald-400">{r.tutar} €</td>
                  <td className="p-4"><span className={`px-2.5 py-1 rounded-full text-xs font-medium ${r.durum === "onaylandi" ? "bg-emerald-500/10 text-emerald-400" : "bg-amber-500/10 text-amber-500"}`}>{r.durum}</span></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Full Size Create Modal Overlay */}
      <AnimatePresence>
        {isModalOpen && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm overflow-y-auto">
            <motion.div initial={{ opacity: 0, scale: 0.95 }} animate={{ opacity: 1, scale: 1 }} exit={{ opacity: 0, scale: 0.95 }} className="bg-zinc-900 border border-white/10 p-6 rounded-2xl shadow-xl w-full max-w-4xl my-8">
              <h2 className="text-xl font-bold text-white mb-4 border-b border-white/10 pb-2">Gider Formu Oluştur</h2>
              
              <form onSubmit={handleCreate} className="space-y-4">
                <div className="max-h-[50vh] overflow-y-auto space-y-4 pr-2">
                  {items.map((item, idx) => (
                    <div key={idx} className="p-4 bg-zinc-800/50 border border-white/5 rounded-xl space-y-3 relative">
                      <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div>
                          <label className="block text-xs text-zinc-400 mb-1">Teşkilat</label>
                          <select value={item.teskilat} onChange={(e) => updateItem(idx, "teskilat", e.target.value)} className="w-full bg-zinc-800 border border-white/10 rounded-lg px-2 py-1.5 text-white text-sm">
                            {teskilatListesi.map(t => <option key={t} value={t}>{t}</option>)}
                          </select>
                        </div>
                        <div>
                          <label className="block text-xs text-zinc-400 mb-1">Birim</label>
                          <select value={item.birim} onChange={(e) => updateItem(idx, "birim", e.target.value)} className="w-full bg-zinc-800 border border-white/10 rounded-lg px-2 py-1.5 text-white text-sm">
                            {birimListesi.map(b => <option key={b.id} value={b.id}>{b.label}</option>)}
                          </select>
                        </div>
                        <div>
                          <label className="block text-xs text-zinc-400 mb-1">Tür</label>
                          <select value={item.tur} onChange={(e) => updateItem(idx, "tur", e.target.value)} className="w-full bg-zinc-800 border border-white/10 rounded-lg px-2 py-1.5 text-white text-sm">
                            {turListesi.map(t => <option key={t.id} value={t.id}>{t.label}</option>)}
                          </select>
                        </div>
                        <div>
                          <label className="block text-xs text-zinc-400 mb-1">Miktar (€)</label>
                          <input type="number" step="0.01" value={item.tutar} onChange={(e) => updateItem(idx, "tutar", e.target.value)} required className="w-full bg-zinc-800 border border-white/10 rounded-lg px-2 py-1.5 text-white text-sm" />
                        </div>
                      </div>
                      <div className="flex gap-2">
                        <input type="text" value={item.aciklama} onChange={(e) => updateItem(idx, "aciklama", e.target.value)} placeholder="Açıklama / Detay" className="flex-grow bg-zinc-800 border border-white/10 rounded-lg px-2 py-1.5 text-white text-sm" />
                        {items.length > 1 && (
                          <button type="button" onClick={() => removeItemRow(idx)} className="p-1 px-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg"><Trash2 className="w-4 h-4" /></button>
                        )}
                      </div>
                    </div>
                  ))}
                </div>

                <button type="button" onClick={addItemRow} className="flex items-center gap-1 text-xs text-emerald-400 hover:text-emerald-300 font-medium"><Plus className="w-4 h-4" /> Yeni Kalem Ekle</button>

                <div className="border-t border-white/5 pt-4 space-y-3">
                  <div className="flex items-center gap-3">
                    <div className="flex-grow">
                      <label className="block text-xs text-zinc-400 mb-1">IBAN (TR/AT)</label>
                      <input type="text" value={iban} onChange={(e) => setIban(e.target.value.toUpperCase())} placeholder="AT.." className="w-full font-mono bg-zinc-800 border border-white/10 rounded-lg px-3 py-2 text-white text-sm" />
                    </div>
                    <div className="text-right">
                      <label className="block text-xs text-zinc-400 mb-1">Toplam Tutar</label>
                      <div className="text-2xl font-bold text-emerald-400">{calculateTotal().toFixed(2)} €</div>
                    </div>
                  </div>
                </div>

                <div className="flex gap-3 pt-2">
                  <button type="submit" disabled={submitLoading} className="flex-1 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-sm font-semibold disabled:opacity-50">
                    Gideri Bildir
                  </button>
                  <button type="button" onClick={() => setIsModalOpen(false)} className="flex-1 py-2 bg-zinc-800 text-white rounded-xl text-sm font-semibold border border-white/5">İptal</button>
                </div>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </div>
  );
}
