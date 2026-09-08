import { Head, useForm } from "@inertiajs/react";
import { route } from "ziggy-js";
import { AuthenticatedLayout } from "@/Components/layout";
import { SectorForm } from "./SectorForm";
import { Pencil } from "lucide-react";

interface EditProps {
    sector: {
        id: number;
        name: string;
        mobile: string | null;
        address: string | null;
        status: string;
    };
}

export default function SectorEdit({ sector }: EditProps) {
    const { data, setData, put, processing, errors } = useForm({
        name: sector.name,
        mobile: sector.mobile ?? "",
        address: sector.address ?? "",
        status: sector.status,
    });

    return (
        <AuthenticatedLayout title={`Edit ${sector.name}`}>
            <Head title={`Edit ${sector.name}`} />
            <SectorForm
                mode="edit"
                sectorId={sector.id}
                data={data}
                setData={setData}
                submit={(url) => put(url, { preserveScroll: true })}
                processing={processing}
                errors={errors}
            />
        </AuthenticatedLayout>
    );
}
