"use client";

import { useState, useEffect, useCallback } from "react";
import type { AdminCategory } from "@/lib/types/admin";

export default function AdminCategoriesPage() {
  const [categories, setCategories] = useState<AdminCategory[]>([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [editId, setEditId] = useState<number | null>(null);
  const [name, setName] = useState("");
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<{ type: "success" | "error"; text: string } | null>(null);

  const fetchCategories = useCallback(async () => {
    setLoading(true);
    try {
      const res = await fetch("/api/v1/admin/categories");
      if (res.ok) {
        setCategories(await res.json());
      } else {
        setMessage({ type: "error", text: "Erreur lors du chargement des categories." });
      }
    } catch {
      setMessage({ type: "error", text: "Erreur reseau." });
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchCategories();
  }, [fetchCategories]);

  useEffect(() => {
    if (!message) return;
    const t = setTimeout(() => setMessage(null), 4000);
    return () => clearTimeout(t);
  }, [message]);

  function resetForm() {
    setName("");
    setEditId(null);
    setShowForm(false);
    setSaving(false);
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setMessage(null);

    try {
      if (editId) {
        const res = await fetch(`/api/v1/admin/categories/${editId}`, {
          method: "PATCH",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ name }),
        });
        if (res.ok) {
          setMessage({ type: "success", text: "Categorie mise a jour." });
        } else {
          const data = await res.json();
          setMessage({ type: "error", text: data.message ?? "Erreur lors de la modification." });
          setSaving(false);
          return;
        }
      } else {
        const res = await fetch("/api/v1/admin/categories", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ name }),
        });
        if (res.ok) {
          setMessage({ type: "success", text: "Categorie creee." });
        } else {
          const data = await res.json();
          setMessage({ type: "error", text: data.message ?? "Erreur lors de la creation." });
          setSaving(false);
          return;
        }
      }
      resetForm();
      await fetchCategories();
    } catch {
      setMessage({ type: "error", text: "Erreur reseau." });
      setSaving(false);
    }
  }

  async function handleDelete(id: number) {
    if (!confirm("Supprimer cette categorie ?")) return;
    setMessage(null);
    try {
      const res = await fetch(`/api/v1/admin/categories/${id}`, { method: "DELETE" });
      if (res.ok) {
        setMessage({ type: "success", text: "Categorie supprimee." });
        await fetchCategories();
      } else {
        const data = await res.json();
        setMessage({ type: "error", text: data.message ?? "Erreur lors de la suppression." });
      }
    } catch {
      setMessage({ type: "error", text: "Erreur reseau." });
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">Categories</h1>
        <button
          onClick={() => {
            resetForm();
            setShowForm(!showForm);
          }}
          className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
        >
          {showForm ? "Annuler" : "Ajouter"}
        </button>
      </div>

      {message && (
        <div
          className={`rounded-lg border p-3 text-sm ${
            message.type === "success"
              ? "border-green-200 bg-green-50 text-green-800"
              : "border-red-200 bg-red-50 text-red-800"
          }`}
        >
          {message.text}
        </div>
      )}

      {showForm && (
        <form
          onSubmit={handleSubmit}
          className="flex gap-3 rounded-lg border border-gray-200 bg-white p-4"
        >
          <input
            type="text"
            placeholder="Nom de la categorie"
            value={name}
            onChange={(e) => setName(e.target.value)}
            required
            maxLength={100}
            className="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
          />
          <button
            type="submit"
            disabled={saving || !name.trim()}
            className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
          >
            {saving ? "..." : editId ? "Modifier" : "Creer"}
          </button>
        </form>
      )}

      {loading ? (
        <div className="flex h-32 items-center justify-center">
          <div className="h-6 w-6 animate-spin rounded-full border-4 border-indigo-600 border-t-transparent" />
        </div>
      ) : (
        <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500">Nom</th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500">Slug</th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500">Ordre</th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500">Actif</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {categories.map((cat) => (
                <tr key={cat.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 text-sm font-medium text-gray-900">{cat.name}</td>
                  <td className="px-4 py-3 text-sm text-gray-500">{cat.slug}</td>
                  <td className="px-4 py-3 text-sm text-gray-500">{cat.sort_order}</td>
                  <td className="px-4 py-3">
                    <span
                      className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                        cat.active
                          ? "bg-green-100 text-green-700"
                          : "bg-gray-100 text-gray-500"
                      }`}
                    >
                      {cat.active ? "Oui" : "Non"}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-right">
                    <div className="flex justify-end gap-1">
                      <button
                        onClick={() => {
                          setName(cat.name);
                          setEditId(cat.id);
                          setShowForm(true);
                        }}
                        className="rounded px-2 py-1 text-xs text-indigo-600 hover:bg-indigo-50"
                      >
                        Modifier
                      </button>
                      <button
                        onClick={() => handleDelete(cat.id)}
                        className="rounded px-2 py-1 text-xs text-red-600 hover:bg-red-50"
                      >
                        Supprimer
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {categories.length === 0 && (
            <p className="py-8 text-center text-sm text-gray-500">
              Aucune categorie. Cliquez sur &quot;Ajouter&quot; pour en creer une.
            </p>
          )}
        </div>
      )}
    </div>
  );
}
