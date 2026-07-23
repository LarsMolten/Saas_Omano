import { NextRequest, NextResponse } from "next/server";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

export async function PATCH(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  const cookie = request.headers.get("cookie") ?? "";
  const { id } = await params;
  const body = await request.json();

  const res = await fetch(`${API_URL}/v1/admin/reports/${id}`, {
    method: "PATCH",
    headers: { Cookie: cookie, Accept: "application/json", "Content-Type": "application/json" },
    body: JSON.stringify(body),
  });

  const data = await res.json();
  return NextResponse.json(data, { status: res.status });
}
