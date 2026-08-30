import { Head, router } from "@inertiajs/react";
import { useState } from "react";
import { AuthenticatedLayout } from "@/Components/layout";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    Checkbox,
    Badge,
    Button,
} from "@/Components/ui";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui";
import { ShieldCheck, Save, User as UserIcon } from "lucide-react";
import { toast } from "sonner";

interface AdminUser {
    id: number;
    username: string;
    name: string;
    role: string;
    status: string;
}

interface MenuItem {
    id: number;
    name: string;
    route: string | null;
}

interface MenuGroup {
    id: number;
    name: string;
    icon: string;
    children: MenuItem[];
}

interface PermissionState {
    can_view: boolean;
    can_edit: boolean;
    can_delete: boolean;
    can_backdate: boolean;
}

interface PermissionsProps {
    users: AdminUser[];
    menuTree: MenuGroup[];
    permissions: Record<string, PermissionState>;
}

export default function Permissions({ users, menuTree, permissions }: PermissionsProps) {
    const [selectedUserId, setSelectedUserId] = useState<number | null>(
        users[0]?.id ?? null,
    );
    const [localPerms, setLocalPerms] = useState<Record<string, PermissionState>>(permissions);
    const [saving, setSaving] = useState<string | null>(null);

    const selectedUser = users.find((u) => u.id === selectedUserId);

    const permKey = (userId: number, menuId: number) => `${userId}-${menuId}`;

    const getPerm = (menuId: number): PermissionState => {
        if (!selectedUserId) return { can_view: false, can_edit: false, can_delete: false, can_backdate: false };
        return localPerms[permKey(selectedUserId, menuId)] ?? { can_view: false, can_edit: false, can_delete: false, can_backdate: false };
    };

    const togglePerm = (menuId: number, field: keyof PermissionState) => {
        if (!selectedUserId) return;
        const key = permKey(selectedUserId, menuId);
        const current = localPerms[key] ?? { can_view: false, can_edit: false, can_delete: false, can_backdate: false };
        setLocalPerms({
            ...localPerms,
            [key]: { ...current, [field]: !current[field] },
        });
    };

    const savePerm = async (menuId: number, menuName: string) => {
        if (!selectedUserId) return;
        const key = permKey(selectedUserId, menuId);
        const perm = localPerms[key] ?? { can_view: false, can_edit: false, can_delete: false, can_backdate: false };
        setSaving(key);
        router.put(
            `/admin/permissions/${selectedUserId}/${menuId}`,
            { ...perm } as Record<string, boolean>,
            {
                preserveScroll: true,
                onSuccess: () => toast.success(`Permissions updated for ${menuName}`),
                onError: () => toast.error("Failed to update permission"),
                onFinish: () => setSaving(null),
            },
        );
    };

    // Flatten all menu items for the permission matrix
    const allMenus = menuTree.flatMap((g) => g.children);

    return (
        <AuthenticatedLayout
            title="Permission Management"
            actions={
                <Badge variant="accent">
                    <ShieldCheck className="size-3" /> Superadmin
                </Badge>
            }
        >
            <Head title="Permissions" />

            <div className="space-y-6">
                {/* User selector */}
                <Card>
                    <CardHeader>
                        <CardTitle>Select User</CardTitle>
                        <CardDescription>
                            Choose a user to view and modify their menu permissions.
                            Superadmins automatically have full access to all menus.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                            {users.map((u) => (
                                <button
                                    key={u.id}
                                    onClick={() => setSelectedUserId(u.id)}
                                    className={`flex items-center gap-3 p-3 rounded-lg border transition-all text-left ${
                                        selectedUserId === u.id
                                            ? "border-primary bg-primary-soft"
                                            : "border-border hover:border-primary/40 hover:bg-surface-2"
                                    }`}
                                >
                                    <div className="size-8 rounded-full bg-surface-2 flex items-center justify-center shrink-0">
                                        <UserIcon className="size-4 text-muted" />
                                    </div>
                                    <div className="min-w-0">
                                        <p className="text-sm font-medium truncate">{u.name}</p>
                                        <p className="text-xs text-muted truncate">@{u.username}</p>
                                    </div>
                                </button>
                            ))}
                        </div>
                        {users.length === 0 && (
                            <p className="text-sm text-muted text-center py-4">
                                No non-superadmin users found. Create users to manage their permissions.
                            </p>
                        )}
                    </CardContent>
                </Card>

                {/* Permission matrix */}
                {selectedUser && (
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                Permissions for {selectedUser.name}{" "}
                                <span className="text-muted font-normal">@{selectedUser.username}</span>
                            </CardTitle>
                            <CardDescription>
                                Toggle view, edit, delete, and backdate permissions per menu item.
                                Changes are saved individually per row.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="rounded-lg border border-border overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="min-w-[150px] sm:w-[40%]">Menu Item</TableHead>
                                            <TableHead className="text-center">View</TableHead>
                                            <TableHead className="text-center">Edit</TableHead>
                                            <TableHead className="text-center">Delete</TableHead>
                                            <TableHead className="text-center">Backdate</TableHead>
                                            <TableHead className="text-center">Action</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {allMenus.map((menu) => {
                                            const perm = getPerm(menu.id);
                                            const key = permKey(selectedUser.id, menu.id);
                                            return (
                                                <TableRow key={menu.id}>
                                                    <TableCell className="font-medium">{menu.name}</TableCell>
                                                    <TableCell className="text-center">
                                                        <Checkbox
                                                            checked={perm.can_view}
                                                            onCheckedChange={() => togglePerm(menu.id, "can_view")}
                                                        />
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        <Checkbox
                                                            checked={perm.can_edit}
                                                            onCheckedChange={() => togglePerm(menu.id, "can_edit")}
                                                        />
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        <Checkbox
                                                            checked={perm.can_delete}
                                                            onCheckedChange={() => togglePerm(menu.id, "can_delete")}
                                                        />
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        <Checkbox
                                                            checked={perm.can_backdate}
                                                            onCheckedChange={() => togglePerm(menu.id, "can_backdate")}
                                                        />
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() => savePerm(menu.id, menu.name)}
                                                            disabled={saving === key}
                                                        >
                                                            <Save className="size-3" />
                                                            {saving === key ? "Saving…" : "Save"}
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
