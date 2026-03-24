"use client";

import { useState } from "react";
import { Mail, Plus, Edit, Trash2, Search, Package, AlertCircle } from "lucide-react";

export default function SablonlarPage() {
  const [loading] = useState(false);
  const [search] = useState("");

  const [sablonlar] = useState([
    { id: 1, baslik: "Toplantı Daveti", konu: "Yeni Toplantı Planlandı", tip: "Email", created_at: "2024-03-24T12:00:00Z" },
    { id: 2, baslik: "İzin Onaylandı", konu: "İzin Talebiniz Hakkında", tip: "Email", created_at: "2024-03-23T10:00:00Z" },
    { id: 3, baslik: "Harcama Reddedildi", konu: "Harcama Talebi Durumu", tip: "Email", created_at: "2024-03-22T08:30:00Z" },
  ]);

  return (
    <div className="space-y-6">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
            <Mail className="w-6 h-6 text-emerald-400" /> Şablon Yönetimi
          </h1>
          <p className="text-zinc-500 text-sm mt-1">Sistem tarafından gönderilen otomatik mail ve bildirim şablonlarını yönetin.</p>
        </div>
        <div className="flex gap-2">
           <button className="bg-emerald-600 hover:bg-emerald-500 text-white font-medium px-4 py-2 rounded-xl transition-all flex items-center gap-2 text-sm shadow-lg shadow-emerald-500/20">
             <Plus className="w-4 h-4" /> Yeni Şablon
           </button>
        </div>
      </div>

      <div className="bg-zinc-900 border border-white/5 rounded-2xl p-4 sticky top-20 z-10 shadow-xl shadow-black/20 flex gap-4">
         <div className="relative max-w-sm w-full">
            <Search className="w-4 h-4 absolute left-3 top-3 text-zinc-500" />
            <input type="text" placeholder="Şablon adı veya konu ara..." disabled className="w-full bg-zinc-950 border border-white/10 rounded-xl pl-10 pr-4 py-2 text-sm text-zinc-300 focus:outline-none focus:border-emerald-500/50 cursor-not-allowed" />
         </div>
         <div className="flex items-center gap-1.5 text-xs text-zinc-400">
           <AlertCircle className="w-3.5 h-3.5" /> Statik veri gösterilmektedir. 
         </div>
      </div>

      <div className="bg-zinc-900 border border-white/5 rounded-2xl overflow-hidden shadow-xl">
         <table className="w-full text-left text-sm whitespace-nowrap">
            <thead className="bg-zinc-950/50 text-zinc-400 font-medium">
               <tr>
                  <th className="px-5 py-4 pl-6">Şablon Adı</th>
                  <th className="px-5 py-4">Mail Konusu</th>
                  <th className="px-5 py-4">Yazılım Tipi</th>
                  <th className="px-5 py-4">Tarih</th>
                  <th className="px-5 py-4 text-right pr-6">İşlemler</th>
               </tr>
            </thead>
            <tbody className="divide-y divide-white/5">
               {sablonlar.map(s => (
                  <tr key={s.id} className="hover:bg-white/[0.02] transition-colors group">
                     <td className="px-5 py-4 pl-6">
                        <div className="font-bold text-zinc-200">{s.baslik}</div>
                     </td>
                     <td className="px-5 py-4">
                        <div className="text-zinc-300 font-medium">{s.konu}</div>
                     </td>
                     <td className="px-5 py-4">
                        <span className="bg-amber-500/10 text-amber-500 border border-amber-500/20 text-xs px-1.5 py-0.5 rounded-md font-bold uppercase">{s.tip}</span>
                     </td>
                     <td className="px-5 py-4">
                        <span className="text-zinc-500 text-xs">{new Date(s.created_at).toLocaleDateString("tr-TR")}</span>
                     </td>
                     <td className="px-5 py-4 text-right pr-6 space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button className="p-1.5 bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 rounded-lg transition-colors">
                           <Edit className="w-4 h-4" />
                        </button>
                        <button className="p-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-500 rounded-lg transition-colors">
                           <Trash2 className="w-4 h-4" />
                        </button>
                     </td>
                  </tr>
               ))}
            </tbody>
         </table>
      </div>
    </div>
  );
}
