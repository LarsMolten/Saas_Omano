"use client";

import { useState } from "react";
import type { Service, ServiceOption } from "@/lib/types/service";

interface ServiceFormProps {
  providerId: number;
  service?: Service | null;
  onSave: (service: Service) => void;
  onDelete?: () => void;
  onCancel: () => void;
}

interface OptionDraft {
  id?: number;
  label: string;
  extra_price: string;
}

export default function ServiceForm({
  providerId,
  service,
  onSave,
  onDelete,
  onCancel,
}: ServiceFormProps) {
  const [title, setTitle] = useState(service?.title ?? "");
  const [description, setDescription] = useState(service?.description ?? "");
  const [price, setPrice] = useState(service?.price ?? "");
  const [priceType, setPriceType] = useState<string>(
    service?.price_type ?? "fixed"
  );
  const [position, setPosition] = useState<string>(
    service?.position?.toString() ?? "0"
  );
  const [options, setOptions] = useState<OptionDraft[]>(
    service?.options?.map((o) => ({
      id: o.id,
      label: o.label,
      extra_price: o.extra_price,
    })) ?? []
  );
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  function addOption() {
    setOptions([...options, { label: "", extra_price: "0" }]);
  }

  function updateOption(index: number, field: keyof OptionDraft, value: string) {
    const updated = [...options];
    updated[index] = { ...updated[index], [field]: value };
    setOptions(updated);
  }

  function removeOption(index: number) {
    setOptions(options.filter((_, i) => i !== index));
  }

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setError("");
    setLoading(true);

    const payload = {
      title,
      description: description || null,
      price: priceType === "quote" ? null : price ? parseFloat(price) : null,
      price_type: priceType,
      position: parseInt(position, 10) || 0,
      options: options
        .filter((o) => o.label.trim())
        .map((o) => ({
          id: o.id,
          label: o.label,
          extra_price: parseFloat(o.extra_price) || 0,
        })),
    };

    try {
      const method = service ? "PATCH" : "POST";
      const url = service
        ? `/api/v1/services/${service.id}`
        : `/api/v1/providers/${providerId}/services`;

      const res = await fetch(url, {
        method,
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      const data = await res.json();

      if (!res.ok) {
        setError(data.message || "Erreur lors de la sauvegarde.");
        return;
      }

      onSave(data);
    } catch {
      setError("Erreur réseau.");
    } finally {
      setLoading(false);
    }
  }

  async function handleDelete() {
    if (!service || !onDelete) return;

    if (!confirm("Supprimer ce service ?")) return;

    try {
      const res = await fetch(`/api/v1/services/${service.id}`, {
        method: "DELETE",
      });

      if (!res.ok) {
        const data = await res.json();
        setError(data.message || "Erreur lors de la suppression.");
        return;
      }

      onDelete();
    } catch {
      setError("Erreur réseau.");
    }
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-4 bg-white p-6 rounded-lg border">
      {error && (
        <div className="bg-red-50 text-red-700 p-3 rounded text-sm">{error}</div>
      )}

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          Titre *
        </label>
        <input
          type="text"
          value={title}
          onChange={(e) => setTitle(e.target.value)}
          required
          className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          Description
        </label>
        <textarea
          value={description}
          onChange={(e) => setDescription(e.target.value)}
          rows={3}
          className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <div className="grid grid-cols-3 gap-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Type de prix *
          </label>
          <select
            value={priceType}
            onChange={(e) => setPriceType(e.target.value)}
            className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="fixed">Prix fixe</option>
            <option value="from">À partir de</option>
            <option value="quote">Sur devis</option>
          </select>
        </div>

        {priceType !== "quote" && (
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Prix (€)
            </label>
            <input
              type="number"
              step="0.01"
              min="0"
              value={price}
              onChange={(e) => setPrice(e.target.value)}
              className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
        )}

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Position
          </label>
          <input
            type="number"
            min="0"
            value={position}
            onChange={(e) => setPosition(e.target.value)}
            className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>

      <div>
        <div className="flex items-center justify-between mb-2">
          <label className="block text-sm font-medium text-gray-700">
            Options
          </label>
          <button
            type="button"
            onClick={addOption}
            className="text-sm text-blue-600 hover:underline"
          >
            + Ajouter une option
          </button>
        </div>

        {options.length > 0 && (
          <div className="space-y-2">
            {options.map((option, index) => (
              <div key={index} className="flex gap-2 items-center">
                <input
                  type="text"
                  placeholder="Libellé"
                  value={option.label}
                  onChange={(e) => updateOption(index, "label", e.target.value)}
                  className="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="+€"
                  value={option.extra_price}
                  onChange={(e) =>
                    updateOption(index, "extra_price", e.target.value)
                  }
                  className="w-24 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <button
                  type="button"
                  onClick={() => removeOption(index)}
                  className="text-red-500 hover:text-red-700 text-sm"
                >
                  ✕
                </button>
              </div>
            ))}
          </div>
        )}
      </div>

      <div className="flex gap-3 pt-2">
        <button
          type="submit"
          disabled={loading}
          className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50"
        >
          {loading ? "Sauvegarde..." : service ? "Modifier" : "Ajouter"}
        </button>

        <button
          type="button"
          onClick={onCancel}
          className="border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50"
        >
          Annuler
        </button>

        {service && onDelete && (
          <button
            type="button"
            onClick={handleDelete}
            className="border border-red-300 text-red-700 px-4 py-2 rounded-md hover:bg-red-50 ml-auto"
          >
            Supprimer
          </button>
        )}
      </div>
    </form>
  );
}
