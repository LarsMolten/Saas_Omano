"use client";

import { useState, useEffect, useCallback } from "react";
import type { AdminReport, PaginatedResponse } from "@/lib/types/admin";

const REASON_LABELS: Record<string, string> = {
  inappropriate: "Inapproprie",
  spam: "Spam",
  fake: "Faux",
  other: "Autre",
};

export default function AdminReportsPage() {
  const [data, setData] = useState<PaginatedResponse<AdminReport> | null>(null);
  const [statusFilter, setStatusFilter] = useState("pending");
  const [loading, setLoading] = useState(true);
  const [resolvingId, setResolvingId] = useState<number | null>(null);
  const [note, setNote] = useState("");

  const fetchReports = useCallback(async () => {
    setLoading(true);
    const params = new URLSearchParams();
    if (statusFilter) params.set("status", statusFilter);
    const res = await fetch(`/api/v1/admin/reports?${params}`);
    if (res.ok) setData(await res.json());
    setLoading(false);
  }, [statusFilter]);

  useEffect(() => {
    fetchReports();
  }, [fetchReports]);

  async function resolve(id: number, action: string) {
    await fetch(`/api/v1/admin/reports/${id}`, {
      method: "PATCH",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action, resolution_note: note || null }),
    });
    setResolvingId(null);
    setNote("");
    fetchReports();
  }

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900">Signalements</h1>

      <div className="flex gap-3">
        <select
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value)}
          className="rounded-lg border border-gray-300 px-3 py-2 text-sm"
        >
          <option value="pending">En attente</option>
          <option value="">Tous</option>
          <option value="dismissed">Rejetes</option>
          <option value="sanctioned">Sanctionnes</option>
        </select>
      </div>

      {loading ? (
        <div className="flex h-32 items-center justify-center">
          <div className="h-6 w-6 animate-spin rounded-full border-4 border-indigo-600 border-t-transparent" />
        </div>
      ) : (
        <div className="space-y-3">
          {data?.data.map((r) => (
            <div
              key={r.id}
              className="rounded-lg border border-gray-200 bg-white p-4"
            >
              <div className="flex items-start justify-between">
                <div>
                  <div className="flex items-center gap-2">
                    <span
                      className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                        r.reportable_type === "review"
                          ? "bg-blue-100 text-blue-700"
                          : "bg-purple-100 text-purple-700"
                      }`}
                    >
                      {r.reportable_type === "review" ? "Avis" : "Profil"}
                    </span>
                    <span className="text-xs text-gray-500">
                      Signale par {r.reporter?.name ?? "Inconnu"}
                    </span>
                  </div>
                  <p className="mt-1 text-sm text-gray-900">
                    Raison: {REASON_LABELS[r.reason] ?? r.reason}
                  </p>
                  {r.description && (
                    <p className="mt-1 text-sm text-gray-600">{r.description}</p>
                  )}
                  <p className="mt-1 text-xs text-gray-400">
                    {new Date(r.created_at).toLocaleString("fr-FR")}
                  </p>
                </div>

                {r.status === "pending" && (
                  <div className="flex items-center gap-2">
                    {resolvingId === r.id ? (
                      <div className="flex items-center gap-2">
                        <input
                          type="text"
                          placeholder="Note (optionnel)"
                          value={note}
                          onChange={(e) => setNote(e.target.value)}
                          className="rounded border border-gray-300 px-2 py-1 text-xs"
                        />
                        <button
                          onClick={() => resolve(r.id, "dismiss")}
                          className="rounded px-2 py-1 text-xs text-gray-600 hover:bg-gray-100"
                        >
                          Rejeter
                        </button>
                        <button
                          onClick={() => resolve(r.id, "content_deleted")}
                          className="rounded px-2 py-1 text-xs text-amber-600 hover:bg-amber-50"
                        >
                          Supprimer contenu
                        </button>
                        <button
                          onClick={() => resolve(r.id, "sanction")}
                          className="rounded px-2 py-1 text-xs text-red-600 hover:bg-red-50"
                        >
                          Sanctionner
                        </button>
                        <button
                          onClick={() => {
                            setResolvingId(null);
                            setNote("");
                          }}
                          className="rounded px-2 py-1 text-xs text-gray-400"
                        >
                          Annuler
                        </button>
                      </div>
                    ) : (
                      <button
                        onClick={() => setResolvingId(r.id)}
                        className="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700"
                      >
                        Traiter
                      </button>
                    )}
                  </div>
                )}

                {r.status !== "pending" && (
                  <span className="text-xs text-gray-500">
                    {r.status === "dismissed" ? "Rejete" : "Sanctionne"} par{" "}
                    {r.resolver?.name ?? "Inconnu"}
                  </span>
                )}
              </div>
            </div>
          ))}
          {data?.data.length === 0 && (
            <p className="py-8 text-center text-sm text-gray-500">
              Aucun signalement
            </p>
          )}
        </div>
      )}
    </div>
  );
}
