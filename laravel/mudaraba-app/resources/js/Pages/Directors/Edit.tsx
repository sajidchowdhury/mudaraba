import { Head, useForm } from "@inertiajs/react";
import { route } from "ziggy-js";
import { AuthenticatedLayout } from "@/Components/layout";
import { DirectorForm } from "./DirectorForm";
import { Pencil } from "lucide-react";

interface EditProps {
    director: {
        id: number;
        name: string;
        mobile: string | null;
        address: string | null;
        is_my: boolean;
    };
}

export default function DirectorEdit({ director }: EditProps) {
    const { data, setData, put, processing, errors } = useForm({
        name: director.name,
        mobile: director.mobile ?? "",
        address: director.address ?? "",
        is_my: director.is_my,
    });

    return (
        <AuthenticatedLayout title={`Edit ${director.name}`}>
            <Head title={`Edit ${director.name}`} />
            <DirectorForm
                mode="edit"
                directorId={director.id}
                data={data}
                setData={setData}
                submit={(url) => put(url, { preserveScroll: true })}
                processing={processing}
                errors={errors}
            />
        </AuthenticatedLayout>
    );
}
