"use client";

import { useUser } from "@/lib/hooks/useUser";
import { useEffect, useState } from "react";

interface SubscriptionInfo {
  subscription: { plan: string; period: string; status: string; ends_at: string } | null;
  plan: string;
  limits: Record<string, unknown>;
  remaining: { media: number | null; services: number | null };
}

export default function AbonnementPage() {
  const { user } = useUser();
  const [info, setInfo] = useState<SubscriptionInfo | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch("/api/v1/subscriptions/current")
      .then((r) => (r.ok ? r.json() : null))
      .then((data) => setInfo(data))
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  if (!user) return null;

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900">Mon abonnement</h1>

      {loading ? (
        <div className="py-8 text-center text-gray-500">Chargement...</div>
      ) : !info?.subscription ? (
        <div className="bg-white rounded-xl border p-8 text-center">
          <p className="text-gray-500 text-lg mb-4">Aucun abonnement actif.</p>
          <a
            href="/dashboard/abonnement"
            className="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700"
          >
            Decouvrir les plans
          </a>
        </div>
      ) : (
        <div className="bg-white rounded-xl border p-6 space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-lg font-semibold text-gray-900 capitalize">
                Plan {info.subscription.plan}
              </h2>
              <p className="text-sm text-gray-500">
                {info.subscription.period === "monthly" ? "Mensuel" : "Annuel"}
              </p>
            </div>
            <span className={`px-3 py-1 rounded-full text-sm font-medium ${
              info.subscription.status === "active"
                ? "bg-green-100 text-green-800"
                : "bg-gray-100 text-gray-800"
            }`}>
              {info.subscription.status === "active" ? "Actif" : info.subscription.status}
            </span>
          </div>

          <dl className="grid grid-cols-2 gap-4 text-sm border-t pt-4">
            <div>
              <dt className="text-gray-500">Expire le</dt>
              <dd className="font-medium text-gray-900">
                {new Date(info.subscription.ends_at).toLocaleDateString("fr-FR")}
              </dd>
            </div>
            <div>
              <dt className="text-gray-500">Services restants</dt>
              <dd className="font-medium text-gray-900">
                {info.remaining.services !== null ? info.remaining.services : "Illimite"}
              </dd>
            </div>
            <div>
              <dt className="text-gray-500">Media restants</dt>
              <dd className="font-medium text-gray-900">
                {info.remaining.media !== null ? info.remaining.media : "Illimite"}
              </dd>
            </div>
          </dl>
        </div>
      )}
    </div>
  );
}
