"use client";

import { useState, useEffect } from "react";

interface FavoriteButtonProps {
  providerId: number;
  initialFavorited?: boolean;
  size?: "sm" | "md";
}

export default function FavoriteButton({
  providerId,
  initialFavorited = false,
  size = "md",
}: FavoriteButtonProps) {
  const [favorited, setFavorited] = useState(initialFavorited);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    async function checkFavorite() {
      try {
        const res = await fetch("/api/v1/favorites");
        if (res.ok) {
          const favorites: { id: number }[] = await res.json();
          setFavorited(favorites.some((f) => f.id === providerId));
        }
      } catch {
        // silent
      }
    }
    checkFavorite();
  }, [providerId]);

  async function toggle(e: React.MouseEvent) {
    e.preventDefault();
    e.stopPropagation();
    if (loading) return;

    setLoading(true);
    try {
      if (favorited) {
        const res = await fetch(`/api/v1/favorites/${providerId}`, {
          method: "DELETE",
        });
        if (res.ok) setFavorited(false);
      } else {
        const res = await fetch("/api/v1/favorites", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ provider_id: providerId }),
        });
        if (res.ok) setFavorited(true);
      }
    } catch {
      // silent
    } finally {
      setLoading(false);
    }
  }

  const sizeClasses = size === "sm" ? "w-8 h-8 text-sm" : "w-10 h-10 text-lg";

  return (
    <button
      onClick={toggle}
      disabled={loading}
      className={`${sizeClasses} flex items-center justify-center rounded-full border transition-colors ${
        favorited
          ? "bg-red-50 border-red-200 text-red-500"
          : "bg-white border-gray-200 text-gray-400 hover:text-red-400"
      } disabled:opacity-50`}
      title={favorited ? "Retirer des favoris" : "Ajouter aux favoris"}
    >
      {favorited ? "♥" : "♡"}
    </button>
  );
}
