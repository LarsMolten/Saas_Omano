"use client";

import { useState, useCallback } from "react";
import type { SearchResponse, SearchFilters } from "@/lib/types/search";
import { buildSearchParams } from "@/lib/api";
import SearchFiltersBar from "./SearchFiltersBar";
import ProviderCard from "./ProviderCard";

interface SearchPageProps {
  initialData: SearchResponse;
}

export default function SearchPage({ initialData }: SearchPageProps) {
  const [results, setResults] = useState<SearchResponse>(initialData);
  const [loading, setLoading] = useState(false);
  const [filters, setFilters] = useState<SearchFilters>({});

  const doSearch = useCallback(async (params: SearchFilters) => {
    setLoading(true);
    try {
      const qs = buildSearchParams(params as Record<string, string | number | undefined>);
      const res = await fetch(`/api/v1/search?${qs}`);
      if (res.ok) {
        const data: SearchResponse = await res.json();
        setResults(data);
      }
    } catch {
      // silent
    } finally {
      setLoading(false);
    }
  }, []);

  function handleFilterChange(newFilters: SearchFilters) {
    setFilters(newFilters);
    doSearch(newFilters);
  }

  function handlePageChange(page: number) {
    const next = { ...filters, page };
    setFilters(next);
    doSearch(next);
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  const { data, meta } = results;

  return (
    <div className="space-y-6">
      <SearchFiltersBar filters={filters} onChange={handleFilterChange} />

      <div className="flex items-center justify-between">
        <p className="text-sm text-gray-600">
          {meta.total} resultat{meta.total !== 1 ? "s" : ""}
        </p>
        {loading && <p className="text-sm text-blue-600">Recherche...</p>}
      </div>

      {data.length === 0 ? (
        <div className="text-center py-16">
          <p className="text-gray-500 text-lg">
            Aucun prestataire ne correspond a vos criteres.
          </p>
          <button
            onClick={() => handleFilterChange({})}
            className="mt-4 text-blue-600 hover:underline"
          >
            Reinitialiser les filtres
          </button>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {data.map((provider) => (
            <ProviderCard key={provider.id} provider={provider} />
          ))}
        </div>
      )}

      {meta.last_page > 1 && (
        <div className="flex justify-center gap-2 mt-8">
          {Array.from({ length: meta.last_page }, (_, i) => i + 1)
            .filter(
              (p) =>
                p === 1 ||
                p === meta.last_page ||
                Math.abs(p - meta.current_page) <= 2
            )
            .reduce<(number | "ellipsis")[]>((acc, p, i, arr) => {
              if (i > 0 && p - (arr[i - 1] as number) > 1) {
                acc.push("ellipsis");
              }
              acc.push(p);
              return acc;
            }, [])
            .map((item, i) =>
              item === "ellipsis" ? (
                <span key={`e${i}`} className="px-3 py-2 text-gray-400">
                  ...
                </span>
              ) : (
                <button
                  key={item}
                  onClick={() => handlePageChange(item)}
                  className={`px-3 py-2 rounded text-sm font-medium ${
                    item === meta.current_page
                      ? "bg-blue-600 text-white"
                      : "bg-white border hover:bg-gray-50"
                  }`}
                >
                  {item}
                </button>
              )
            )}
        </div>
      )}
    </div>
  );
}
