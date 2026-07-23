"use client";

import { useState, useEffect } from "react";
import {
  ResponsiveContainer,
  AreaChart,
  Area,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
} from "recharts";
import type { StatsResponse, StatsTotals } from "@/lib/types/stats";

const PERIODS = [
  { value: "7d", label: "7 jours" },
  { value: "30d", label: "30 jours" },
  { value: "12m", label: "12 mois" },
] as const;

const METRICS: { key: keyof StatsTotals; label: string; color: string }[] = [
  { key: "visits", label: "Visites", color: "#6366f1" },
  { key: "clicks", label: "Clics", color: "#06b6d4" },
  { key: "contacts", label: "Contacts", color: "#10b981" },
  { key: "favorites_count", label: "Favoris", color: "#f59e0b" },
  { key: "quote_requests_count", label: "Devis", color: "#ef4444" },
];

function StatCard({ label, value }: { label: string; value: number }) {
  return (
    <div className="rounded-lg border border-gray-200 bg-white p-4">
      <p className="text-sm text-gray-500">{label}</p>
      <p className="mt-1 text-2xl font-bold text-gray-900">{value}</p>
    </div>
  );
}

export default function StatsDashboard() {
  const [period, setPeriod] = useState<"7d" | "30d" | "12m">("7d");
  const [data, setData] = useState<StatsResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setLoading(true);
    setError(null);

    fetch(`/api/v1/stats?period=${period}`)
      .then(async (res) => {
        if (res.status === 403) {
          const body = await res.json();
          throw new Error(body.message ?? "Accès refusé");
        }
        if (!res.ok) throw new Error("Erreur de chargement");
        return res.json();
      })
      .then((json: StatsResponse) => setData(json))
      .catch((e: Error) => setError(e.message))
      .finally(() => setLoading(false));
  }, [period]);

  function formatDate(d: string) {
    const date = new Date(d);
    if (period === "12m") {
      return date.toLocaleDateString("fr-FR", { month: "short", year: "2-digit" });
    }
    return date.toLocaleDateString("fr-FR", { day: "2-digit", month: "short" });
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-lg font-semibold text-gray-900">Évolution</h2>
        <div className="flex gap-1 rounded-lg border border-gray-200 bg-white p-1">
          {PERIODS.map((p) => (
            <button
              key={p.value}
              onClick={() => setPeriod(p.value)}
              className={`rounded-md px-3 py-1.5 text-sm font-medium transition-colors ${
                period === p.value
                  ? "bg-indigo-600 text-white"
                  : "text-gray-600 hover:bg-gray-50"
              }`}
            >
              {p.label}
            </button>
          ))}
        </div>
      </div>

      {error && (
        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
          {error}
        </div>
      )}

      {loading && (
        <div className="flex h-64 items-center justify-center">
          <div className="h-8 w-8 animate-spin rounded-full border-4 border-indigo-600 border-t-transparent" />
        </div>
      )}

      {!loading && data && (
        <>
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            {METRICS.map((m) => (
              <StatCard key={m.key} label={m.label} value={data.totals[m.key]} />
            ))}
          </div>

          <div className="rounded-lg border border-gray-200 bg-white p-4">
            {data.daily.length === 0 ? (
              <p className="py-12 text-center text-sm text-gray-500">
                Aucune donnée pour cette période
              </p>
            ) : (
              <ResponsiveContainer width="100%" height={350}>
                <AreaChart data={data.daily}>
                  <defs>
                    {METRICS.map((m) => (
                      <linearGradient key={m.key} id={`grad-${m.key}`} x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor={m.color} stopOpacity={0.15} />
                        <stop offset="95%" stopColor={m.color} stopOpacity={0} />
                      </linearGradient>
                    ))}
                  </defs>
                  <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                  <XAxis
                    dataKey="date"
                    tickFormatter={formatDate}
                    tick={{ fontSize: 12, fill: "#6b7280" }}
                    tickLine={false}
                    axisLine={false}
                  />
                  <YAxis
                    tick={{ fontSize: 12, fill: "#6b7280" }}
                    tickLine={false}
                    axisLine={false}
                    allowDecimals={false}
                  />
                  <Tooltip
                    labelFormatter={(label) => formatDate(String(label))}
                    contentStyle={{
                      borderRadius: "0.5rem",
                      border: "1px solid #e5e7eb",
                      fontSize: "0.875rem",
                    }}
                  />
                  <Legend wrapperStyle={{ fontSize: "0.875rem" }} />
                  {METRICS.map((m) => (
                    <Area
                      key={m.key}
                      type="monotone"
                      dataKey={m.key}
                      name={m.label}
                      stroke={m.color}
                      fill={`url(#grad-${m.key})`}
                      strokeWidth={2}
                    />
                  ))}
                </AreaChart>
              </ResponsiveContainer>
            )}
          </div>
        </>
      )}
    </div>
  );
}
