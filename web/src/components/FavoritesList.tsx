"use client";

import { useState, useEffect } from "react";
import type { Favorite } from "@/lib/types/favorite";

export default function FavoritesList() {
  const [favorites, setFavorites] = useState<Favorite[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch("/api/v1/favorites")
      .then((res) => (res.ok ? res.json() : []))
      .then((data) => setFavorites(data))
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  function handleRemove(id: number) {
    fetch(`/api/v1/favorites/${id}`, { method: "DELETE" })
      .then((res) => {
        if (res.ok) setFavorites((prev) => prev.filter((f) => f.id !== id));
      })
      .catch(() => {});
  }

  if (loading) {
    return <div className="py-8 text-center text-gray-500">Chargement...</div>;
  }

  if (favorites.length === 0) {
    return (
      <div className="text-center py-16">
        <p className="text-gray-500 text-lg">Aucun prestataire en favori.</p>
        <a href="/recherche" className="mt-4 inline-block text-blue-600 hover:underline">
          Explorer les prestataires
        </a>
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      {favorites.map((provider) => (
        <div
          key={provider.id}
          className="bg-white rounded-lg shadow-sm border p-4 flex items-start justify-between"
        >
          <div className="flex-1 min-w-0">
            <h3 className="font-semibold text-gray-900 truncate">{provider.name}</h3>
            {provider.category && (
              <span className="inline-block mt-1 text-xs bg-blue-100 text-blue-800 rounded px-2 py-0.5">
                {provider.category}
              </span>
            )}
            {provider.bio && (
              <p className="mt-2 text-sm text-gray-600 line-clamp-2">{provider.bio}</p>
            )}
            <div className="mt-2 flex items-center gap-3 text-sm text-gray-500">
              {provider.city && <span>{provider.city}</span>}
              {parseFloat(provider.average_rating) > 0 && (
                <span className="flex items-center gap-1">
                  <svg className="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  {parseFloat(provider.average_rating).toFixed(1)}
                </span>
              )}
            </div>
          </div>
          <button
            onClick={() => handleRemove(provider.id)}
            className="ml-3 text-red-400 hover:text-red-600 text-sm"
            title="Retirer des favoris"
          >
            ♥ Retirer
          </button>
        </div>
      ))}
    </div>
  );
}
