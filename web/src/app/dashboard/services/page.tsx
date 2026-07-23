"use client";

import { useUser } from "@/lib/hooks/useUser";
import { useEffect, useState } from "react";
import type { Service } from "@/lib/types/service";
import ServiceList from "@/components/services/ServiceList";
import ServiceForm from "@/components/services/ServiceForm";

export default function ServicesPage() {
  const { user } = useUser();
  const [services, setServices] = useState<Service[]>([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing] = useState<Service | null>(null);

  useEffect(() => {
    if (!user) return;
    fetch(`/api/v1/providers/${user.id}/services`)
      .then((r) => (r.ok ? r.json() : []))
      .then((data) => setServices(Array.isArray(data) ? data : []))
      .catch(() => {})
      .finally(() => setLoading(false));
  }, [user]);

  function handleSave(service: Service) {
    if (editing) {
      setServices((prev) => prev.map((s) => (s.id === service.id ? service : s)));
    } else {
      setServices((prev) => [...prev, service]);
    }
    setShowForm(false);
    setEditing(null);
  }

  function handleDelete() {
    if (!editing) return;
    fetch(`/api/v1/services/${editing.id}`, { method: "DELETE" })
      .then(() => {
        setServices((prev) => prev.filter((s) => s.id !== editing.id));
        setShowForm(false);
        setEditing(null);
      })
      .catch(() => {});
  }

  if (!user) return null;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">Mes services</h1>
        {!showForm && (
          <button
            onClick={() => { setEditing(null); setShowForm(true); }}
            className="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700"
          >
            + Ajouter un service
          </button>
        )}
      </div>

      {showForm ? (
        <ServiceForm
          providerId={user.id}
          service={editing}
          onSave={handleSave}
          onDelete={editing ? handleDelete : undefined}
          onCancel={() => { setShowForm(false); setEditing(null); }}
        />
      ) : loading ? (
        <div className="py-8 text-center text-gray-500">Chargement...</div>
      ) : (
        <div>
          {services.length === 0 ? (
            <div className="text-center py-16 bg-white rounded-xl border">
              <p className="text-gray-500">Aucun service publie.</p>
              <button
                onClick={() => setShowForm(true)}
                className="mt-4 text-blue-600 hover:underline text-sm"
              >
                Ajouter votre premier service
              </button>
            </div>
          ) : (
            <div className="space-y-4">
              {services.map((service) => (
                <div
                  key={service.id}
                  className="bg-white rounded-xl border p-4 flex items-center justify-between"
                >
                  <ServiceList services={[service]} />
                  <button
                    onClick={() => { setEditing(service); setShowForm(true); }}
                    className="ml-4 text-sm text-blue-600 hover:underline shrink-0"
                  >
                    Modifier
                  </button>
                </div>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
}
