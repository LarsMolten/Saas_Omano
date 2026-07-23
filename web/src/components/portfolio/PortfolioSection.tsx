"use client";

import { useState, useEffect, useCallback } from "react";
import type { PortfolioItem } from "@/lib/types/portfolio";
import PortfolioList from "./PortfolioList";
import PortfolioForm from "./PortfolioForm";

interface PortfolioSectionProps {
  providerId: number;
}

export default function PortfolioSection({ providerId }: PortfolioSectionProps) {
  const [items, setItems] = useState<PortfolioItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState<PortfolioItem | null>(null);
  const [showForm, setShowForm] = useState(false);

  const fetchItems = useCallback(async () => {
    try {
      const res = await fetch(`/api/v1/providers/${providerId}/portfolio`);
      if (res.ok) {
        setItems(await res.json());
      }
    } catch {
      // silent
    } finally {
      setLoading(false);
    }
  }, [providerId]);

  useEffect(() => {
    fetchItems();
  }, [fetchItems]);

  function handleSave(item: PortfolioItem) {
    if (editing) {
      setItems((prev) =>
        prev.map((i) => (i.id === item.id ? item : i))
      );
    } else {
      setItems((prev) => [...prev, item]);
    }
    setShowForm(false);
    setEditing(null);
  }

  function handleDelete() {
    if (!editing) return;
    const id = editing.id;
    fetch(`/api/v1/portfolio/${id}`, { method: "DELETE" })
      .then(() => {
        setItems((prev) => prev.filter((i) => i.id !== id));
        setShowForm(false);
        setEditing(null);
      })
      .catch(() => {});
  }

  if (loading) {
    return <div className="py-8 text-center text-gray-500">Chargement...</div>;
  }

  if (showForm) {
    return (
      <div>
        <h2 className="text-lg font-semibold mb-4">
          {editing ? "Modifier la réalisation" : "Nouvelle réalisation"}
        </h2>
        <PortfolioForm
          providerId={providerId}
          item={editing}
          onSave={handleSave}
          onDelete={editing ? handleDelete : undefined}
          onCancel={() => {
            setShowForm(false);
            setEditing(null);
          }}
        />
      </div>
    );
  }

  return (
    <PortfolioList
      items={items}
      onEdit={(item) => {
        setEditing(item);
        setShowForm(true);
      }}
      onAdd={() => {
        setEditing(null);
        setShowForm(true);
      }}
    />
  );
}
