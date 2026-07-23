import { NextRequest, NextResponse } from "next/server";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

export async function PATCH(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  const { id } = await params;
  const cookie = request.headers.get("cookie") ?? "";

  const res = await fetch(`${API_URL}/v1/notifications/${id}/read`, {
    method: "PATCH",
    headers: { Cookie: cookie, Accept: "application/json" },
  });

  const data = await res.json();
  return NextResponse.json(data, { status: res.status });
}
