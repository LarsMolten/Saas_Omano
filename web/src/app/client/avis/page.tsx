"use client";

import { useEffect, useState } from "react";

interface Review {
  id: number;
  provider_id: number;
  rating: number;
  comment: string | null;
  status: string;
  created_at: string;
  provider?: { id: number; name: string; slug: string };
}

export default function ClientAvisPage() {
  const [reviews, setReviews] = useState<Review[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch("/api/v1/quotes")
      .then((r) => (r.ok ? r.json() : []))
      .then(() => {
        // Reviews aren't directly listable by client yet,
        // so we show a placeholder
        setLoading(false);
      })
      .catch(() => setLoading(false));
  }, []);

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900">Mes avis</h1>

      {loading ? (
        <div className="py-8 text-center text-gray-500">Chargement...</div>
      ) : (
        <div className="bg-white rounded-xl border p-8 text-center">
          <p className="text-gray-500">
            Vos avis apparaissent sur les profils des prestataires que vous avez consultes.
          </p>
          <p className="text-sm text-gray-400 mt-2">
            Vous pouvez laisser un avis apres avoir recu un devis d&apos;un prestataire.
          </p>
        </div>
      )}
    </div>
  );
}
