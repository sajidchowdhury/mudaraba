import { Head, useForm } from "@inertiajs/react";
import { route } from "ziggy-js";
import { AuthenticatedLayout } from "@/Components/layout";
import { SectorForm } from "./SectorForm";
import { PlusCircle } from "lucide-react";

export default function SectorCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: "",
        mobile: "",
        address: "",
        status: "active",
    });

    return (
        <AuthenticatedLayout title="New Sector">
            <Head title="New Sector" />
            <SectorForm
                mode="create"
                data={data}
                setData={setData}
                submit={(url) => post(url, { preserveScroll: true })}
                processing={processing}
                errors={errors}
            />
        </AuthenticatedLayout>
    );
}
