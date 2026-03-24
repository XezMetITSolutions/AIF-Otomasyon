"use client";

import { useState } from "react";
import { BarChart3, TrendingUp, Users, Calendar, Download } from "lucide-react";

export default function RaporlarPage() {
  const [loading] = useState(false);

  return (
    <div className="space-y-6">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
            <BarChart3 className="w-6 h-6 text-emerald-400" /> Raporlar & Analizler
          </h1>
          <p className="text-zinc-500 text-sm mt-1">BYK performans verilerini ve istatistikleri görselleştirin.</p>
        </div>
        <button className="bg-zinc-800 hover:bg-zinc-700 text-white font-medium px-4 py-2 rounded-xl transition-all flex items-center gap-2 text-sm shadow-lg shadow-black/20 border border-white/5">
           <Download className="w-4 h-4" /> PDF İndir
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
         <div className="bg-zinc-900 border border-white/5 rounded-2xl p-5 shadow-xl">
            <div className="flex justify-between items-start mb-4">
               <span className="text-xs text-zinc-500 font-semibold">Toplam Etkinlik</span>
               <Calendar className="w-5 h-5 text-emerald-500" />
            </div>
            <div className="text-2xl font-bold text-white">42</div>
            <div className="text-[10px] text-zinc-600 mt-1 flex items-center gap-1">
               <TrendingUp className="w-3 h-3 text-emerald-500" /> %12 artış (geçen aya göre)
            </div>
         </div>

         <div className="bg-zinc-900 border border-white/5 rounded-2xl p-5 shadow-xl">
            <div className="flex justify-between items-start mb-4">
               <span className="text-xs text-zinc-500 font-semibold">Aktif Üyeler</span>
               <Users className="w-5 h-5 text-sky-500" />
            </div>
            <div className="text-2xl font-bold text-white">128</div>
            <div className="text-[10px] text-zinc-600 mt-1">Son 1 ayda aktif</div>
         </div>
      </div>

      <div className="bg-zinc-900 border border-white/5 rounded-2xl p-6 min-h-[300px] flex flex-col justify-center items-center text-center opacity-70">
         <BarChart3 className="w-12 h-12 text-zinc-500 mb-2" />
         <h3 className="text-sm font-semibold text-zinc-300">Detaylı Grafik Analizleri</h3>
         <p className="text-xs text-zinc-500 max-w-sm mt-1">Bu modül üzerinden aylık katılım raporlarını ve organizasyon grafiklerini yakında interaktif olarak görebileceksiniz.</p>
      </div>
    </div>
  );
}
