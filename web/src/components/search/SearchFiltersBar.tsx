"use client";

import { useState } from "react";
import type { SearchFilters } from "@/lib/types/search";
import { CATEGORIES, CITIES } from "@/lib/types/search";

interface SearchFiltersBarProps {
  filters: SearchFilters;
  onChange: (filters: SearchFilters) => void;
}

export default function SearchFiltersBar({
  filters,
  onChange,
}: SearchFiltersBarProps) {
  const [q, setQ] = useState(filters.q ?? "");
  const [category, setCategory] = useState(filters.category ?? "");
  const [city, setCity] = useState(filters.city ?? "");
  const [priceMin, setPriceMin] = useState(filters.price_min?.toString() ?? "");
  const [priceMax, setPriceMax] = useState(filters.price_max?.toString() ?? "");
  const [ratingMin, setRatingMin] = useState(filters.rating_min?.toString() ?? "");
  const [verified, setVerified] = useState(filters.verified ?? "");

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    onChange({
      q: q || undefined,
      category: category || undefined,
      city: city || undefined,
      price_min: priceMin ? Number(priceMin) : undefined,
      price_max: priceMax ? Number(priceMax) : undefined,
      rating_min: ratingMin ? Number(ratingMin) : undefined,
      verified: verified || undefined,
      page: 1,
    });
  }

  function handleReset() {
    setQ("");
    setCategory("");
    setCity("");
    setPriceMin("");
    setPriceMax("");
    setRatingMin("");
    setVerified("");
    onChange({});
  }

  return (
    <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow-sm border p-4">
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="sm:col-span-2 lg:col-span-4">
          <input
            type="text"
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Rechercher par nom, description..."
            className="w-full border rounded px-3 py-2 text-sm"
          />
        </div>

        <div>
          <label className="block text-xs font-medium text-gray-500 mb-1">
            Categorie
          </label>
          <select
            value={category}
            onChange={(e) => setCategory(e.target.value)}
            className="w-full border rounded px-3 py-2 text-sm"
          >
            <option value="">Toutes</option>
            {CATEGORIES.map((c) => (
              <option key={c} value={c}>
                {c}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label className="block text-xs font-medium text-gray-500 mb-1">
            Ville
          </label>
          <select
            value={city}
            onChange={(e) => setCity(e.target.value)}
            className="w-full border rounded px-3 py-2 text-sm"
          >
            <option value="">Toutes</option>
            {CITIES.map((c) => (
              <option key={c} value={c}>
                {c}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label className="block text-xs font-medium text-gray-500 mb-1">
            Prix min (OMR)
          </label>
          <input
            type="number"
            min="0"
            step="0.01"
            value={priceMin}
            onChange={(e) => setPriceMin(e.target.value)}
            className="w-full border rounded px-3 py-2 text-sm"
            placeholder="0"
          />
        </div>

        <div>
          <label className="block text-xs font-medium text-gray-500 mb-1">
            Prix max (OMR)
          </label>
          <input
            type="number"
            min="0"
            step="0.01"
            value={priceMax}
            onChange={(e) => setPriceMax(e.target.value)}
            className="w-full border rounded px-3 py-2 text-sm"
            placeholder="999"
          />
        </div>

        <div>
          <label className="block text-xs font-medium text-gray-500 mb-1">
            Note min
          </label>
          <select
            value={ratingMin}
            onChange={(e) => setRatingMin(e.target.value)}
            className="w-full border rounded px-3 py-2 text-sm"
          >
            <option value="">Toutes</option>
            <option value="1">1+</option>
            <option value="2">2+</option>
            <option value="3">3+</option>
            <option value="4">4+</option>
            <option value="4.5">4.5+</option>
          </select>
        </div>

        <div>
          <label className="block text-xs font-medium text-gray-500 mb-1">
            Verifie
          </label>
          <select
            value={verified}
            onChange={(e) => setVerified(e.target.value)}
            className="w-full border rounded px-3 py-2 text-sm"
          >
            <option value="">Tous</option>
            <option value="true">Verifie</option>
            <option value="false">Non verifie</option>
          </select>
        </div>

        <div className="flex items-end gap-2">
          <button
            type="submit"
            className="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700"
          >
            Rechercher
          </button>
          <button
            type="button"
            onClick={handleReset}
            className="border px-4 py-2 rounded text-sm hover:bg-gray-50"
          >
            Reset
          </button>
        </div>
      </div>
    </form>
  );
}
