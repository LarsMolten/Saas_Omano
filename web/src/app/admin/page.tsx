"use client";

import { useState, useEffect } from "react";
import type { AdminStats } from "@/lib/types/admin";

export default function AdminDashboard() {
  const [stats, setStats] = useState<AdminStats | null>(null);

  useEffect(() => {
    fetch("/api/v1/admin/stats")
      .then((r) => (r.ok ? r.json() : null))
      .then(setStats)
      .catch(() => {});
  }, []);

  if (!stats) {
    return (
      <div className="flex h-64 items-center justify-center">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-indigo-600 border-t-transparent" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900">Tableau de bord</h1>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card title="Utilisateurs" value={stats.users.total} sub={`${stats.users.clients} clients, ${stats.users.prestataires} prestataires`} />
        <Card title="Prestataires actifs" value={stats.users.active_prestataires} sub={`${stats.users.suspended} suspendus, ${stats.users.banned} bannis`} />
        <Card title="Abonnements actifs" value={stats.subscriptions.active} sub={Object.entries(stats.subscriptions.by_plan).map(([p, n]) => `${p}: ${n}`).join(", ") || "Aucun"} />
        <Card title="Revenus totaux" value={`${stats.revenue.total.toFixed(2)} OMR`} sub={Object.entries(stats.revenue.by_plan).map(([p, v]) => `${p}: ${Number(v).toFixed(2)}`).join(", ") || "Aucun"} />
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <Card title="Signalements en attente" value={stats.reports.pending} sub="À traiter" highlight={stats.reports.pending > 0} />
        <Card title="Avis signalés" value={stats.reviews.reported} sub={`sur ${stats.reviews.total} total`} />
      </div>
    </div>
  );
}

function Card({ title, value, sub, highlight }: { title: string; value: string | number; sub: string; highlight?: boolean }) {
  return (
    <div className={`rounded-lg border p-5 ${highlight ? "border-amber-300 bg-amber-50" : "border-gray-200 bg-white"}`}>
      <p className="text-sm text-gray-500">{title}</p>
      <p className="mt-1 text-2xl font-bold text-gray-900">{value}</p>
      <p className="mt-1 text-xs text-gray-400">{sub}</p>
    </div>
  );
}
